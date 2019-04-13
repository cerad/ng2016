<?php

namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use AppBundle\Action\Project\User\ProjectUser;
use Doctrine\DBAL\DBALException;

use AppBundle\Action\Services\VolCerts;

use Swift_Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Fed\Ayso\AysoFinder;
use Zayso\Fed\FedPerson;
use Zayso\Reg\Person\RegPerson;
use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersonMapper;
use Zayso\Reg\Person\RegPersonSaver;

class RegisterController extends AbstractController2
{
    private $registerForm;
    private $fedRepository;
    private $projectPersonRepository;
    private $volCerts;

    private $regPersonFinder;
    private $regPersonMapper;
    private $regPersonSaver;
    private $fedPersonFinder;

    private $successRouteName;
    private $templateEmail;

    private $refereeBadgeUser;

    public function __construct(
        RegisterForm $registerForm,
        ProjectPersonRepositoryV2 $projectPersonRepository,
        PhysicalAysoRepository $fedRepository,
        string $successRouteName,
        RegisterTemplateEmail $templateEmail,
        VolCerts $volCerts,
        RegPersonFinder $regPersonFinder,
        RegPersonMapper $regPersonMapper,
        RegPersonSaver  $regPersonSaver,
        AysoFinder      $fedPersonFinder
    ) {
        $this->registerForm = $registerForm;
        $this->fedRepository = $fedRepository;
        $this->projectPersonRepository = $projectPersonRepository;

        $this->successRouteName = $successRouteName;
        $this->templateEmail = $templateEmail;
        $this->volCerts = $volCerts;

        $this->regPersonFinder = $regPersonFinder;
        $this->regPersonMapper = $regPersonMapper;
        $this->regPersonSaver  = $regPersonSaver;
        $this->fedPersonFinder = $fedPersonFinder;
    }
    public function __invoke(Request $request) : ?Response
    {
        $regPerson = $this->findRegPersonForUser($this->getUser());
        dump($regPerson);

        $regPersonArray = $this->regPersonMapper->toArray2016($regPerson);

        $projectPerson = $this->findProjectPersonForUser($this->getUser());

        $projectPersonArray = $projectPerson->toArray();

        // Real hack here
        $regPersonArray    ['refereeBadge'] = $regPerson->refereeBadgeUser;
        $projectPersonArray['refereeBadge'] = $projectPerson->getRefereeBadgeUser();

        $registerForm = $this->registerForm;
        $registerForm->setData($regPersonArray);
        $registerForm->handleRequest($request);

        if ($registerForm->isValid()) {

            $projectPersonArray = $registerForm->getData();

            $this->refereeBadgeUser = $projectPersonArray['refereeBadge'];
            if ($this->refereeBadgeUser === 'None') {
                $this->refereeBadgeUser = null;
            }
            $regPerson = $this->regPersonMapper->fromArray2016($projectPersonArray);
            dump($regPerson);
            $this->processRegPerson($regPerson);

            // Maybe reset referee info?
            if ($registerForm->getSubmit() == 'nope') {
                $regPerson->setFromArray([
                    'registered' => false,
                    'verified'   => false, // was null ?
                ]);
            }
            dump($regPerson);
            $this->regPersonSaver->save($regPerson);
            dump($regPerson);

            //$projectPerson = (new ProjectPerson)->fromArray($projectPersonArray);
            
            //$projectPerson = $this->process($projectPerson);

            // Maybe reset referee info?
            //if ($registerForm->getSubmit() == 'nope') {
            //    $projectPerson->registered = false;
            //    $projectPerson->verified   = null;
            //}
            //$this->projectPersonRepository->save($projectPerson);

            if ($regPerson->isRegistered === true) {
                //$this->sendEmail($projectPerson);
            }
            return $this->redirectToRoute($this->successRouteName);
        }

        return null;
    }

    /**
     * @param ProjectPerson $projectPerson
     * @return ProjectPerson
     * @throws DBALException
     */
    private function process(ProjectPerson $projectPerson)
    {
        $fedKey = $projectPerson->fedKey;

        // Probably only want to do this if aysoid has changed
        $vol = $this->fedRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender = $vol['gender'];
        }

        // Want to referee?
        $projectPerson = $this->processReferee($projectPerson);

        // Want to volunteer
        $projectPerson = $this->processVolunteer($projectPerson);

        // Need some notifications here?
        $projectPerson->registered = true;

        //Update MY, SAR, Certs with e3 data
        $fedKeyParts =  explode(':', $fedKey);
        $aysoid = isset($fedKeyParts[1]) ? $fedKeyParts[1]: null;
        $e3Certs = (object) $this->volCerts->retrieveVolCertData($aysoid);
        $sar = explode('/', $e3Certs->SAR);
        if(isset($sar[2])){
            $projectPerson->orgKey = 'AYSOR:' . $sar[2];
        }
        $projectPerson->regYear = $e3Certs->MY;
        if (isset($projectPerson->roles['CERT_REFEREE'])) {
            $certDesc = explode(' ', $e3Certs->RefCertDesc);
            $projectPerson->roles['CERT_REFEREE']->badge = isset($certDesc[0]) ? $certDesc[0] : '';
            $projectPerson->roles['CERT_REFEREE']->badgeDate = $e3Certs->RefCertDate;
            $projectPerson->roles['CERT_REFEREE']->verified = !empty($e3Certs->RefCertDate);
        }
        if (isset($projectPerson->roles['CERT_SAFE_HAVEN'])) {
            $projectPerson->roles['CERT_SAFE_HAVEN']->badgeDate = $e3Certs->SafeHavenDate;
            $projectPerson->roles['CERT_SAFE_HAVEN']->verified = !empty($e3Certs->SafeHavenDate);
        }
        if (isset($projectPerson->roles['CERT_CONCUSSION'])) {
            $projectPerson->roles['CERT_CONCUSSION']->badgeDate = $e3Certs->CDCDate;
            $projectPerson->roles['CERT_CONCUSSION']->verified = !empty($e3Certs->CDCDate);
        }
        return $projectPerson;
    }
    private function processRegPerson(RegPerson $regPerson) : void
    {
        // Update fed info
        $fedPerson = $this->fedPersonFinder->find($regPerson->fedPersonId);

        if ($fedPerson) {

            // Add youth to notes
            $notes = $regPerson->notes;
            if (!$notes) {
                if ($fedPerson->ageGroup === 'Youth') $notes = 'Youth';
            }
            $regPerson->setFromArray([
                'fedOrgId' => $fedPerson->fedOrgId,
                'regYear'  => $fedPerson->memYear,
                'notes'    => $notes,
            ]);
        }

        // Want to referee?
        $this->processRegReferee($regPerson,$fedPerson);

        // Want to volunteer
        $this->processRegVolunteer($regPerson,$fedPerson);

        // Need some notifications here?
        $regPerson->set('registered',true);
    }

    /**
     * @param ProjectPerson $projectPerson
     * @return ProjectPerson
     * @throws DBALException
     */
    private function processReferee(ProjectPerson $projectPerson)
    {
        // Only do this if they said they would referee
        $willReferee = strtolower($projectPerson->plans['willReferee']) !== 'no' ? true : false;
        if (!$willReferee) {
            return $projectPerson;
        }

        $fedKey = $projectPerson->fedKey;

        $roleKey = 'ROLE_REFEREE';
        $refereeRole = $projectPerson->getRole($roleKey, true);
        $projectPerson->addRole($refereeRole);

        // Referee Cert
        $certKey = 'CERT_REFEREE';
        $refereeCert = $projectPerson->getCert($certKey,true);
        $refereeCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey,$certKey);

        if ($cert) {
            $refereeCert->roleDate  = $cert['roleDate'];
            $refereeCert->badge     = $cert['badge'];
            $refereeCert->badgeUser = $cert['badge'];
            $refereeCert->badgeDate = $cert['badgeDate'];
            $refereeCert->verified  = true;
        }
        // User selected badge on registration form
        if ($this->refereeBadgeUser) {
            $refereeCert->badgeUser = $this->refereeBadgeUser;
            if (!$refereeCert->badge) {
                $refereeCert->badge = $this->refereeBadgeUser;
            }
        }
        $projectPerson->addCert($refereeCert);

        // Safe Haven
        $certKey = 'CERT_SAFE_HAVEN';
        $safeHavenCert = $projectPerson->getRole($certKey, true);

        $safeHavenCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey, $certKey);

        if ($cert) {
            $safeHavenCert->badge = $cert['badge'];
            $safeHavenCert->verified = true;
        }
        $projectPerson->addRole($safeHavenCert);

        // Concussion Awareness
        $certKey = 'CERT_CONCUSSION';
        $concCert = $projectPerson->getCert($certKey, true);

        $concCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey, $certKey);

        if ($cert) {
            $concCert->badge = $cert['badge'];
            $concCert->verified = true;
        }
        $projectPerson->addCert($concCert);

        // Done
        return $projectPerson;
    }
    private function processRegReferee(RegPerson $regPerson, ?FedPerson $fedPerson) : void
    {
        // Only do this if they said they would referee
        if (!$regPerson->willReferee) {
            return;
        }
        // This is the role, not the cert!
        $roleKey = 'ROLE_REFEREE';
        $refereeRole = $regPerson->getRole($roleKey, true);
        $regPerson->addRole($refereeRole);

        // Referee Cert
        $certKey = 'CERT_REFEREE';

        $cert = $regPerson->getCert($certKey,true);

        $cert->set('active',false);

        $fedCert = $fedPerson ? $fedPerson->certs->get($certKey) : null;
        if ($fedCert) {
            $cert->setFromArray([
                'roleDate'  => $fedCert->roleDate,
                'badge'     => $fedCert->badge,
                'badgeUser' => $fedCert->badge,
                'badgeDate' => $fedCert->badgeDate,
                'verified'  => $fedCert->isVerified,
            ]);
        }
        $regPerson->addCert($cert);

        // User selected badge on registration form
        // TODO Think about badge user and it it worth the hassle
        if ($this->refereeBadgeUser) {
            $cert->set('badgeUser',$this->refereeBadgeUser);
        }
        if (!$cert->badge) {
            $cert->set('badge',$this->refereeBadgeUser);
        }

        // Safe Haven
        $certKey = 'CERT_SAFE_HAVEN';

        $cert = $regPerson->getCert($certKey, true);

        $cert->set('active',false);

        $fedCert = $fedPerson ? $fedPerson->certs->get($certKey) : null;
        if ($fedCert) {
            $cert->setFromArray([
                'roleDate'  => $fedCert->roleDate,
                'badge'     => $fedCert->badge,
                'badgeDate' => $fedCert->badgeDate,
                'verified'  => $fedCert->isVerified,
            ]);
        }
        $regPerson->addCert($cert);

        // Concussion Awareness
        $certKey = 'CERT_CONCUSSION';

        $cert = $regPerson->getCert($certKey, true);

        $cert->set('active',false);

        $fedCert = $fedPerson ? $fedPerson->certs->get($certKey) : null;
        if ($fedCert) {
            $cert->setFromArray([
                'roleDate'  => $fedCert->roleDate,
                'badge'     => $fedCert->badge,
                'badgeDate' => $fedCert->badgeDate,
                'verified'  => $fedCert->isVerified,
            ]);
        }
        $regPerson->addCert($cert);
    }

    /**
     * @param ProjectPerson $projectPerson
     * @return ProjectPerson
     * @throws DBALException
     */
    private function processVolunteer(ProjectPerson $projectPerson)
    {
        // Only do this if they said they would referee
        $willVolunteer = strtolower($projectPerson->plans['willVolunteer']) !== 'no' ? true : false;
        if (!$willVolunteer) {
            return $projectPerson;
        }

        $fedKey = $projectPerson->fedKey;

        $roleKey = 'ROLE_VOLUNTEER';
        $volunteerRole = $projectPerson->getRole($roleKey, true);
        $projectPerson->addRole($volunteerRole);

        // Safe Haven
        $certKey = 'CERT_SAFE_HAVEN';
        $safeHavenCert = $projectPerson->getRole($certKey, true);

        $safeHavenCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey, $certKey);

        if ($cert) {
            $safeHavenCert->badge = $cert['badge'];
            $safeHavenCert->verified = true;
        }
        $projectPerson->addRole($safeHavenCert);

        // Done
        return $projectPerson;
    }
    private function processRegVolunteer(RegPerson $regPerson, ?FedPerson $fedPerson) : void
    {
        // Only do this if they said they would volunteer
        if (!$regPerson->willVolunteer) {
            return;
        }
        $roleKey = 'ROLE_VOLUNTEER';
        $volunteerRole = $regPerson->getRole($roleKey, true);
        $regPerson->addRole($volunteerRole);

        // Safe Haven
        $certKey = 'CERT_SAFE_HAVEN';

        $cert = $regPerson->getCert($certKey, true);

        $cert->set('active',false);

        $fedCert = $fedPerson ? $fedPerson->certs->get($certKey) : null;
        if ($fedCert) {
            $cert->setFromArray([
                'roleDate'  => $fedCert->roleDate,
                'badge'     => $fedCert->badge,
                'badgeDate' => $fedCert->badgeDate,
                'verified'  => $fedCert->isVerified,
            ]);
        }
        $regPerson->addCert($cert);
    }

    private function findRegPersonForUser(ProjectUser $user) : ?RegPerson
    {
        $projectId = $user['projectKey'];
        $personId  = $user['personKey'];

        // Existing
        $regPerson = $this->regPersonFinder->findByProjectPerson($projectId,$personId);
        if ($regPerson) {
            return $regPerson;
        }
        // Try to clone

        // Brand new
        $data = [
            'projectKey' => $projectId,
            'personKey'  => $personId,
            'email'      => $user['email'],
            'name'       => $this->regPersonSaver->generateUniqueName($projectId,$user['name']),
        ];
        return $this->regPersonMapper->fromArray2016($data);

    }
    /**
     * @param $user
     * @return ProjectPerson|null
     * @throws DBALException
     */
    private function findProjectPersonForUser($user)
    {
        $projectPersonRepository = $this->projectPersonRepository;

        $projectKey = $user['projectKey'];
        $personKey = $user['personKey'];

        // Existing
        $projectPerson = $projectPersonRepository->find($projectKey, $personKey);
        if ($projectPerson) {
            return $projectPerson;
        }
        // Search previous tournaments
        $projectPerson = $projectPersonRepository->find('AYSONationalGames2014', $personKey);

        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2012', $personKey);
        }

        if (!$projectPerson) {
            // Brand new entry
            $projectPerson = $projectPersonRepository->create($projectKey, $personKey, $user['name'], $user['email']);
            $projectPerson->name = $projectPersonRepository->generateUniqueName($projectKey, $projectPerson->name);

            return $projectPerson;
        }
        // Clone from previous machines
        $projectPerson->clearId();
        $projectPerson->projectKey = $projectKey;
        $projectPerson->plans = [];
        $projectPerson->avail = [];

        $fedKey = $projectPerson->fedKey;
        $fedRepository = $this->fedRepository;

        $vol = $fedRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender = $vol['gender'];
        }
        if ($projectPerson->age) {
            $projectPerson->age += 2;
        }
        // Xfer the certs
        foreach ($projectPerson->getCerts() as $certKey => $projectPersonCert) {

            $projectPersonCert->clearId();

            $cert = $fedRepository->findVolCert($fedKey, $certKey);

            if ($cert) {
                $projectPersonCert->roleDate = $cert['roleDate'];
                $projectPersonCert->badge = $cert['badge'];
                $projectPersonCert->badgeUser = $cert['badge'];
                $projectPersonCert->badgeDate = $cert['badgeDate'];
                $projectPersonCert->verified = true;
            }
            $projectPerson->addCert($projectPersonCert);
        }
        // Remove the roles
        foreach ($projectPerson->getRoles() as $role) {
            $projectPerson->removeRole($role);
        }

        return $projectPerson;
    }

    /**
     * @param $person
     */
    private function sendEmail($person)
    {
        $projectInfo = $this->getCurrentProjectInfo();
        $support = $projectInfo['support'];
        $assignor = $projectInfo['assignor'];
        $refAdmin = $projectInfo['administrator'];

        $update = $person['id'] ? ' Update' : null;

        $subject = sprintf('[NG2019] Registration%s for: %s',$update,$person['name']);

        $html = $this->templateEmail->renderHtml($person);

        $toms = [
            $refAdmin['email'] => $refAdmin['name'], // Tom B
            $assignor['email'] => $assignor['name'], // Tom T
        ];

        $mailer = $this->getMailer();

        /** @var Swift_Message $message */
        $message = $mailer->createMessage();

        $message->setBody($html, 'text/html');

        $message->setSubject($subject);

        $message->setFrom(['noreply@zayso.org' => 'zAYSO Admin']);

        $message->setTo([$person['email'] => $person['name']]);

        $message->setCc($toms);

        $message->setReplyTo($toms);

        $message->setBcc(
            [
                $support['email'] => $support['name'],
                'web.ng2019@gmail.com' => 'Rick Roberts', // ???
            ]
        );

        /**  noinspection PhpParamsInspection */
        $mailer->send($message);

    }
}
