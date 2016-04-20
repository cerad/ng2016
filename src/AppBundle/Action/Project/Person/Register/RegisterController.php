<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPersonRepository;
use Symfony\Component\HttpFoundation\Request;

class RegisterController extends AbstractController2
{
    private $registerForm;
    private $fedRepository;
    private $projectPersonRepository;

    private $successRouteName;
    private $templateEmail;

    public function __construct(
        RegisterForm            $registerForm,
        ProjectPersonRepository $projectPersonRepository,
        PhysicalAysoRepository  $fedRepository,
                                $successRouteName,
        RegisterTemplateEmail   $templateEmail
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

        // Real hack here
        $projectPerson['refereeBadge'] = isset($projectPerson['roles']['ROLE_REFEREE']) ?
            $projectPerson['roles']['ROLE_REFEREE']['badgeUser'] :
            null;

        $registerForm = $this->registerForm;
        $registerForm->setData($projectPerson);
        $registerForm->handleRequest($request);
        
        if ($registerForm->isValid()) {

            $projectPersonOriginal = $projectPerson;

            $projectPerson = $registerForm->getData();
            
            $projectPerson = $this->process($projectPerson,$projectPersonOriginal);

            // Maybe reset referee info?
            if ($registerForm->getSubmit() == 'nope') {
                $projectPerson['registered'] = false;
                $projectPerson['verified']   = null;
            }
            $this->projectPersonRepository->save($projectPerson,$projectPersonOriginal);

            // Careful about the id
            if ($projectPerson['registered'] === true) {
                $this->sendEmail($projectPerson);
            }
            return $this->redirectToRoute($this->successRouteName);
        }
        $request->attributes->set('projectPerson',$projectPerson);
        
        return null;
    }
    private function process($projectPerson,$projectPersonOriginal)
    {
        $projectPersonRepository = $this->projectPersonRepository;

        $fedKey = $projectPerson['fedKey'];
        $fedRepository = $this->fedRepository;
        
        $vol = $fedRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson['orgKey']  = $vol['orgKey'];
            $projectPerson['regYear'] = $vol['regYear'];
            $projectPerson['gender']  = $vol['gender'];
        }
        
        // Want to referee?
        $plans = $projectPerson['plans'];
        $willReferee = $plans['willReferee'] !== 'no' ? true : false;
        if ($willReferee) {
             $projectPersonRole =
                isset($projectPerson['roles']['ROLE_REFEREE']) ?
                      $projectPerson['roles']['ROLE_REFEREE'] :
                      $projectPersonRepository->createRole('ROLE_REFEREE');
            $projectPersonRole['projectPersonId'] = $projectPerson['id'];
            $cert = $this->fedRepository->findVolCert($fedKey,'ROLE_REFEREE');
            if ($cert) {
                $projectPersonRole['roleDate']  = $cert['roleDate'];
                $projectPersonRole['badge']     = $cert['badge'];
                $projectPersonRole['badgeUser'] = $cert['badge'];
                $projectPersonRole['badgeDate'] = $cert['badgeDate'];
                $projectPersonRole['verified']  = true;
            }
            if ($projectPerson['refereeBadge']) {
                $projectPersonRole['badgeUser'] = $projectPerson['refereeBadge'];
                if (!$projectPersonRole['badge']) {
                     $projectPersonRole['badge'] = $projectPerson['refereeBadge'];
                }
            }
            $projectPerson['roles']['ROLE_REFEREE'] = $projectPersonRole;
        }
        // Need some notifications here?
        $projectPerson['registered'] = true;

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
            dump($projectPerson);
            return $projectPerson;
        }
        // Clone from previous tournament
        $projectPerson = $projectPersonRepository->find('AYSONationalGames2014',$personKey);

        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->find('AYSONationalGames2012',$personKey);
        }
        
        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->create($projectKey, $personKey, $user['name'], $user['email']);
            $projectPerson['name'] = $projectPersonRepository->generateUniqueName($projectKey,$projectPerson['name']);
            return $projectPerson;
        }
        $projectPerson['id'] = null;
        $projectPerson['projectKey'] = $projectKey;

        $fedKey = $projectPerson['fedKey'];
        $fedRepository = $this->fedRepository;

        $vol = $fedRepository->findVol($fedKey);
        if ($vol) {
            //dump($vol);
            $projectPerson['orgKey']  = $vol['orgKey'];
            $projectPerson['regYear'] = $vol['regYear'];
            $projectPerson['gender']  = $vol['gender'];
        }
        if (isset($projectPerson['age'])) {
            $projectPerson['age'] += 2;
        }
        // Update cert info
        foreach($projectPerson['roles'] as $roleKey => $projectPersonRole) {

            $projectPersonRole['id'] = null;

            $cert = $fedRepository->findVolCert($fedKey,$roleKey);

            if ($cert) {
                $projectPersonRole['roleDate']  = $cert['roleDate'];
                $projectPersonRole['badge']     = $cert['badge'];
                $projectPersonRole['badgeDate'] = $cert['badgeDate'];
                $projectPersonRole['verified']  = true;
            }
            $projectPerson['roles'][$roleKey] = $projectPersonRole;
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

        $message->setFrom(['noreply@zayso.org' => 'Zayso Admin']);

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
