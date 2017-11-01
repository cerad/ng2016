<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController2;

use Cerad\Bundle\AysoBundle\AysoFinder;
use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class RegisterController extends AbstractController2
{
    private $registerForm;
    private $fedFinder;
    private $projectPersonRepository;

    private $successRouteName;
    private $templateEmail;

    private $refereeBadgeUser;

    private $project;

    public function __construct(
        RegisterForm              $registerForm,
        ProjectPersonRepositoryV2 $projectPersonRepository,
        AysoFinder                $fedFinder,
                                  $successRouteName,
        RegisterTemplateEmail     $templateEmail
    )
    {
        $this->registerForm            = $registerForm;
        $this->fedFinder               = $fedFinder;
        $this->projectPersonRepository = $projectPersonRepository;
        
        $this->successRouteName = $successRouteName;
        $this->templateEmail = $templateEmail;
    }
    public function __invoke(Request $request)
    {
        $this->project = $this->getCurrentProjectInfo();

        $projectPerson = $this->findProjectPersonForUser($this->getUser());

        $projectPersonArray = $projectPerson->toArray();

        // Real hack here
        $projectPersonArray['refereeBadge'] = $projectPerson->getRefereeBadgeUser();

        $registerForm = $this->registerForm;
        $registerForm->setData($projectPersonArray);
        $registerForm->handleRequest($request);
        
        if ($registerForm->isValid()) {

            //$projectPersonOriginal = $projectPerson;

            $projectPersonArray = $registerForm->getData();

            $this->refereeBadgeUser = $projectPersonArray['refereeBadge'];
            if ($this->refereeBadgeUser === 'None') {
                $this->refereeBadgeUser = null;
            }
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
    private function process(ProjectPerson $projectPerson)
    {
        $fedKey = $projectPerson->fedKey;

        // Probably only want to do this if aysoid has changed
        $vol = $this->fedFinder->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey  = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender  = $vol['gender'];
        }
        
        // Want to referee?
        $projectPerson = $this->processReferee($projectPerson);

        // Want to volunteer
        $projectPerson = $this->processVolunteer($projectPerson);

        // Need some notifications here?
        $projectPerson->registered = true;

        return $projectPerson;
    }

    /**
     * @param ProjectPerson $projectPerson
     * @return ProjectPerson
     */
    private function processReferee(ProjectPerson $projectPerson)
    {
        // Only do this if they said they would referee
        $willReferee = strtolower($projectPerson->plans['willReferee']) !== 'no' ? true : false;
        if (!$willReferee) return $projectPerson;

        $fedKey = $projectPerson->fedKey;

        $roleKey = 'ROLE_REFEREE';
        $refereeRole = $projectPerson->getRole($roleKey,true);
        $projectPerson->addRole($refereeRole);

        // Referee Cert
        $certKey = 'CERT_REFEREE';
        $refereeCert = $projectPerson->getCert($certKey,true);
        $refereeCert->active = false;

        $cert = $this->fedFinder->findVolCert($fedKey,$certKey);

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
        $safeHavenCert = $projectPerson->getRole($certKey,true);

        $safeHavenCert->active = false;

        $cert = $this->fedFinder->findVolCert($fedKey,$certKey);

        if ($cert) {
            $safeHavenCert->badge = $cert['badge'];
            $safeHavenCert->verified = true;
        }
        $projectPerson->addRole($safeHavenCert);

        // Concussion Awareness
        $certKey = 'CERT_CONCUSSION';
        $concCert = $projectPerson->getCert($certKey,true);

        $concCert->active = false;

        $cert = $this->fedFinder->findVolCert($fedKey,$certKey);

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
     */
    private function processVolunteer(ProjectPerson $projectPerson)
    {
        // Only do this if they said they would referee
        $willVolunteer = strtolower($projectPerson->plans['willVolunteer']) !== 'no' ? true : false;
        if (!$willVolunteer) return $projectPerson;

        $fedKey = $projectPerson->fedKey;

        $roleKey = 'ROLE_VOLUNTEER';
        $volunteerRole = $projectPerson->getRole($roleKey,true);
        $projectPerson->addRole($volunteerRole);

        // Safe Haven
        $certKey = 'CERT_SAFE_HAVEN';
        $safeHavenCert = $projectPerson->getRole($certKey,true);

        $safeHavenCert->active = false;

        $cert = $this->fedFinder->findVolCert($fedKey,$certKey);

        if ($cert) {
            $safeHavenCert->badge = $cert['badge'];
            $safeHavenCert->verified = true;
        }
        $projectPerson->addRole($safeHavenCert);
        
        // Done
        return $projectPerson;
    }
    private function findProjectPersonForUser($user)
    {
        $projectPersonRepository = $this->projectPersonRepository;
        
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        // Existing
        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        if ($projectPerson) {
            return $projectPerson;
        }
        // Search previous tournaments
        $projectPerson = $projectPersonRepository->find('AYSONationalOpenCup2017',$personKey);

        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2016', $personKey);
        }
        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2014', $personKey);
        }

        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2012',$personKey);
        }
        
        if (!$projectPerson) {
            // Brand new entry
            $projectPerson = $projectPersonRepository->create($projectKey, $personKey, $user['name'], $user['email']);
            $projectPerson->name = $projectPersonRepository->generateUniqueName($projectKey,$projectPerson->name);
            return $projectPerson;
        }
        // Clone from previous machines
        $projectPerson->clearId();
        $projectPerson->projectKey = $projectKey;
        $projectPerson->plans = [];
        $projectPerson->avail = [];
        $projectPerson->notes = null;
        $projectPerson->notesUser = null;

        $fedKey = $projectPerson->fedKey;
        $fedFinder = $this->fedFinder;

        $vol = $fedFinder->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey  = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender  = $vol['gender'];
        }
        if ($projectPerson->age) {
            $projectPerson->age += 2; // This needs to be fixed up
        }
        // Xfer the certs
        foreach($projectPerson->getCerts() as $certKey => $projectPersonCert) {
            
            $projectPersonCert->clearId();

            $cert = $fedFinder->findVolCert($fedKey, $certKey);

            if ($cert) {
                $projectPersonCert->roleDate  = $cert['roleDate'];
                $projectPersonCert->badge     = $cert['badge'];
                $projectPersonCert->badgeUser = $cert['badge'];
                $projectPersonCert->badgeDate = $cert['badgeDate'];
                $projectPersonCert->verified  = true;
            }
            $projectPerson->addCert($projectPersonCert);
        }
        // Remove the roles
        foreach($projectPerson->getRoles() as $role) {
            $projectPerson->removeRole($role);
        }
        return $projectPerson;
    }
    private function sendEmail($person)
    {
        $projectInfo = $this->getCurrentProjectInfo();
        $support  = $projectInfo['support'];
        $registration = $projectInfo['registration'];
        $assignor = $projectInfo['assignor'];
        $refAdmin = $projectInfo['administrator'];
        
        $update = $person['id'] ? 'Update' : null;

        $subject = sprintf("[{$this->project['abbv']}] Registration %s for: %s",$update,$person['name']);

        $html = $this->templateEmail->renderHtml($person);

        $toms = [
            $refAdmin['email'] => $refAdmin['name'], // Tom B
            $assignor['email'] => $assignor['name'], // Tom B
        ];

        $mailer = $this->getMailer();

        /** @var \Swift_Message $message */
        $message = $mailer->createMessage();

        $message->setBody($html,'text/html');

        $message->setSubject($subject);

        $message->setFrom(['noreply@zayso.org' => 'zAYSO Admin']);

        $message->setTo([$person['email'] => $person['name']]);
        
        $message->setCc($toms);

        $message->setReplyTo($toms);

        $message->setBcc([
            $support['email'] => $support['name'],
            $registration['email'] => $registration['name'], // ???
        ]);

        /**  noinspection PhpParamsInspection */
        $mailer->send($message);

    }
}
