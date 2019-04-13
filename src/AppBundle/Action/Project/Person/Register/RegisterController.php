<?php

namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Doctrine\DBAL\DBALException;

use AppBundle\Action\Services\VolCerts;

use Swift_Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Reg\Person\RegPerson;
use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersonMapper;

class RegisterController extends AbstractController2
{
    private $registerForm;
    private $fedRepository;
    private $projectPersonRepository;
    private $volCerts;
    private $regPersonFinder;
    private $regPersonMapper;

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
        RegPersonMapper $regPersonMapper
    ) {
        $this->registerForm = $registerForm;
        $this->fedRepository = $fedRepository;
        $this->projectPersonRepository = $projectPersonRepository;

        $this->successRouteName = $successRouteName;
        $this->templateEmail = $templateEmail;
        $this->volCerts = $volCerts;
        $this->regPersonFinder = $regPersonFinder;
        $this->regPersonMapper = $regPersonMapper;
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
        dump($projectPersonArray);
        dump($regPersonArray);
        $registerForm = $this->registerForm;
        $registerForm->setData($regPersonArray);
        $registerForm->handleRequest($request);

        if ($registerForm->isValid()) {

            //$projectPersonOriginal = $projectPerson;

            $projectPersonArray = $registerForm->getData();

            $this->refereeBadgeUser = $projectPersonArray['refereeBadge'];
            if ($this->refereeBadgeUser === 'None') {
                $this->refereeBadgeUser = null;
            }
            $regPerson = $this->regPersonMapper->fromArray2016($projectPersonArray);
            dump($regPerson);

            $projectPerson = (new ProjectPerson)->fromArray($projectPersonArray);
            
            $projectPerson = $this->process($projectPerson);

            // Maybe reset referee info?
            if ($registerForm->getSubmit() == 'nope') {
                $projectPerson->registered = false;
                $projectPerson->verified   = null;
            }
            $this->projectPersonRepository->save($projectPerson);
            // Careful about the id
            if ($projectPerson['registered'] === true) {
                $this->sendEmail($projectPerson);
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

    private function findRegPersonForUser($user) : ?RegPerson
    {
        return $this->regPersonFinder->findByProjectPerson($user->projectId,$user->personId);
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
