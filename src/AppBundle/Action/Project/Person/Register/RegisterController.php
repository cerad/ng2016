<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class RegisterController extends AbstractController2
{
    private $registerForm;
    private $fedRepository;
    private $projectPersonRepository;

    private $successRouteName;
    private $templateEmail;

    private $refereeBadgeUser;

    public function __construct(
        RegisterForm              $registerForm,
        ProjectPersonRepositoryV2 $projectPersonRepository,
        PhysicalAysoRepository    $fedRepository,
                                  $successRouteName,
        RegisterTemplateEmail     $templateEmail
    )
    {
        $this->registerForm            = $registerForm;
        $this->fedRepository           = $fedRepository;
        $this->projectPersonRepository = $projectPersonRepository;
        
        $this->successRouteName = $successRouteName;
        $this->templateEmail = $templateEmail;
    }
    public function __invoke(Request $request)
    {
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
        $vol = $this->fedRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey  = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender  = $vol['gender'];
        }
        
        // Want to referee?
        $projectPerson = $this->processReferee($projectPerson);

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

        $cert = $this->fedRepository->findVolCert($fedKey,$roleKey);

        if ($cert) {
            $refereeRole->roleDate  = $cert['roleDate'];
            $refereeRole->badge     = $cert['badge'];
            $refereeRole->badgeUser = $cert['badge'];
            $refereeRole->badgeDate = $cert['badgeDate'];
            $refereeRole->verified  = true;
        }
        // User selected badge on registration form
        if ($this->refereeBadgeUser) {
            $refereeRole->badgeUser = $this->refereeBadgeUser;
            if (!$refereeRole->badge) {
                 $refereeRole->badge = $this->refereeBadgeUser;
            }
        }
        $projectPerson->addRole($refereeRole);

        // Safe Haven
        $certKey = 'ROLE_SAFE_HAVEN';
        $safeHavenCert = $projectPerson->getRole($certKey,true);

        $safeHavenCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey,$certKey);

        if ($cert) {
            $safeHavenCert->badge = $cert['badge'];
            $safeHavenCert->verified = true;
        }
        $projectPerson->addRole($safeHavenCert);

        // Concussion Awareness
        $certKey = 'CERT_CONCUSSION';
        $concCert = $projectPerson->getRole($certKey,true);

        $concCert->active = false;

        $cert = $this->fedRepository->findVolCert($fedKey,$certKey);

        if ($cert) {
            $concCert->badge = $cert['badge'];
            $concCert->verified = true;
        }
        $projectPerson->addRole($concCert);
        dump($concCert);
        dump($projectPerson);
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
        // Clone from previous tournament
        $projectPerson = $projectPersonRepository->find('AYSONationalGames2014',$personKey);

        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2012',$personKey);
        }
        
        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->create($projectKey, $personKey, $user['name'], $user['email']);
            $projectPerson->name = $projectPersonRepository->generateUniqueName($projectKey,$projectPerson->name);
            return $projectPerson;
        }
        $projectPerson->clearId();
        $projectPerson->projectKey = $projectKey;

        $fedKey = $projectPerson->fedKey;
        $fedRepository = $this->fedRepository;

        $vol = $fedRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson->orgKey  = $vol['orgKey'];
            $projectPerson->regYear = $vol['regYear'];
            $projectPerson->gender  = $vol['gender'];
        }
        if ($projectPerson->age) {
            $projectPerson->age += 2;
        }
        foreach($projectPerson->getRoles() as $roleKey => $projectPersonRole) {

            $projectPersonRole->clearId();

            $cert = $fedRepository->findVolCert($fedKey,$roleKey);

            if ($cert) {
                $projectPersonRole->roleDate  = $cert['roleDate'];
                $projectPersonRole->badge     = $cert['badge'];
                $projectPersonRole->badgeUser = $cert['badge'];
                $projectPersonRole->badgeDate = $cert['badgeDate'];
                $projectPersonRole->verified  = true;
            }
            $projectPerson->addRole($projectPersonRole);
        }
        return $projectPerson;
    }
    private function sendEmail($person)
    {
        $projectInfo = $this->getCurrentProjectInfo();
        $support  = $projectInfo['support'];
        $assignor = $projectInfo['assignor'];
        $refAdmin = $projectInfo['administrator'];
        
        $update = $person['id'] ? ' Update' : null;

        $subject = sprintf('[NG2016] Registration%s for: %s',$update,$person['name']);

        $html = $this->templateEmail->renderHtml($person);

        $toms = [
            $refAdmin['email'] => $refAdmin['name'], // Tom B
            $assignor['email'] => $assignor['name'], // Tom T
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
            'ayso1sra@gmail.com' => 'Rick Roberts', // ???
        ]);

        /**  noinspection PhpParamsInspection */
        $mailer->send($message);

    }
}
