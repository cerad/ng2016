<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Register;

use AppBundle\Action\Project\User\ProjectUser;

use Swift_Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Locator\MailerLocator;
use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Fed\Ayso\AysoFinder;
use Zayso\Fed\Ayso\AysoIdTransformer;
use Zayso\Fed\FedPerson;
use Zayso\Project\CurrentProject;
use Zayso\Reg\Person\RegPerson;
use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersonMapper;
use Zayso\Reg\Person\RegPersonSaver;

class RegisterAction implements ActionInterface
{
    use RouterTrait;
    use AuthenticationTrait;

    private $currentProject;
    private $registerForm;

    private $regPersonFinder;
    private $regPersonMapper;
    private $regPersonSaver;

    private $fedPersonFinder;
    private $fedIdTransformer;

    private $mailerLocator;

    private $successRouteName;

    private $template;
    private $templateEmail;

    private $refereeBadgeUser;

    public function __construct(
        CurrentProject        $currentProject,
        string                $successRouteName,
        RegisterForm          $registerForm,
        RegisterTemplate      $template,
        RegisterTemplateEmail $templateEmail,
        RegPersonFinder       $regPersonFinder,
        RegPersonMapper       $regPersonMapper,
        RegPersonSaver        $regPersonSaver,
        AysoFinder            $fedPersonFinder,
        AysoIdTransformer     $fedIdTransformer,
        MailerLocator         $mailerLocator
    ) {
        $this->currentProject = $currentProject;

        $this->registerForm = $registerForm;

        $this->successRouteName = $successRouteName;
        $this->template         = $template;
        $this->templateEmail    = $templateEmail;

        $this->regPersonFinder  = $regPersonFinder;
        $this->regPersonMapper  = $regPersonMapper;
        $this->regPersonSaver   = $regPersonSaver;

        $this->fedPersonFinder  = $fedPersonFinder;
        $this->fedIdTransformer = $fedIdTransformer;

        $this->mailerLocator    = $mailerLocator;
    }
    public function __invoke(Request $request) : ?Response
    {
        $regPerson = $this->findRegPersonForUser($this->getUser());

        $regPersonArray = $this->regPersonMapper->storeToArray2016($regPerson);

        // Real hack here
        $regPersonArray['refereeBadge'] = $regPerson->refereeBadgeUser;

        $registerForm = $this->registerForm;
        $registerForm->setData($regPersonArray);
        $registerForm->handleRequest($request);

        if ($registerForm->isValid()) {

            $regPersonArray = $registerForm->getData();

            $this->refereeBadgeUser = $regPersonArray['refereeBadge'];
            if ($this->refereeBadgeUser === 'None') {
                $this->refereeBadgeUser = null;
            }
            $regPerson = $this->regPersonMapper->createFromArray2016($regPersonArray);

            $this->processRegPerson($regPerson);

            // Maybe reset referee info?
            if ($registerForm->getSubmit() == 'nope') {
                $regPerson->setFromArray([
                    'registered' => false,
                    'verified'   => false, // was null ?
                ]);
            }
            $regPerson = $this->regPersonSaver->save($regPerson);

            if ($regPerson->isRegistered === true) {
                $this->sendEmail($regPerson);
            }
            return $this->redirectToRoute($this->successRouteName);
        }
        return new Response($this->template->render());
    }
    private function processRegPerson(RegPerson $regPerson) : void
    {
        // todo need a better place for this
        $regPerson->set('registered', true);

        // Set some possible roles
        if ($regPerson->willReferee) {
            $role = $regPerson->getRole('ROLE_REFEREE', true);
            $regPerson->addRole($role);
        }
        else $regPerson->removeRole('ROLE_REFEREE');

        if ($regPerson->willVolunteer) {
            $role = $regPerson->getRole('ROLE_VOLUNTEER', true);
            $regPerson->addRole($role);
        }
        else $regPerson->removeRole('ROLE_VOLUNTEER');

        /* Not really dealing with coaches yet
        if ($regPerson->willCoach) {
            $role = $regPerson->getRole('ROLE_COACH', true);
            $regPerson->addRole($role);
        }
        else $regPerson->removeRole('ROLE_COACH');
        */

        // Update fed info
        $fedPerson = $this->fedPersonFinder->find($regPerson->fedPersonId);

        if ($fedPerson) {

            // Add youth to notes
            $notes = $regPerson->notes;
            if (!$notes) {
                if ($fedPerson->ageGroup === 'Youth') $notes = 'Youth';
            }
            $regPerson->setFromArray([
                'fedPersonId' => $fedPerson->fedPersonId,
                'fedOrgId'    => $fedPerson->fedOrgId,
                'regYear'     => $fedPerson->memYear,
                'notes'       => $notes,
            ]);
        }
        // Bring in certs if we have them
        $this->processCert($regPerson,$fedPerson,'CERT_SAFE_HAVEN');
        $this->processCert($regPerson,$fedPerson,'CERT_CONCUSSION');
        $this->processCert($regPerson,$fedPerson,'CERT_REFEREE');

        // TODO Think about badge user and is it worth the hassle
        if ($this->refereeBadgeUser) {
            $cert = $regPerson->getCert('CERT_REFEREE',true);
            $cert->set('badgeUser',$this->refereeBadgeUser);
            $regPerson->addCert($cert);
        }
        $cert = $regPerson->getCert('CERT_REFEREE',false);
        if ($cert && !$cert->badge) {
            $cert->set('badge', $this->refereeBadgeUser);
            $regPerson->addCert($cert);
        }
    }
    private function processCert(RegPerson $regPerson, ?FedPerson $fedPerson, string $certKey) : void
    {
        if (!$fedPerson) return;

        $fedCert = $fedPerson->certs->get($certKey);
        if (!$fedCert) return;

        $cert = $regPerson->getCert($certKey, true);

        $cert->setFromArray([
            'roleDate'  => $fedCert->roleDate,
            'badge'     => $fedCert->badge,
            'badgeDate' => $fedCert->badgeDate,
            'verified'  => $fedCert->isVerified,
            'active'    => false, // Not sure why but leave for now
        ]);
        $regPerson->addCert($cert);
    }
    private function findRegPersonForUser(ProjectUser $user) : ?RegPerson
    {
        $projectId = $this->currentProject->projectId;
        $personId  = $user->personId;

        // Existing
        $regPerson = $this->regPersonFinder->findByProjectPerson($projectId,$personId);
        if ($regPerson) {
            return $regPerson;
        }
        // Older existing
        $projectIdPrevious = $this->regPersonFinder->findLatestProjectByPerson($personId);

        if ($projectIdPrevious === null) {

            // Brand new
            $data = [
                'projectKey' => $projectId,
                'personKey'  => $personId,
                'email'      => $user['email'],
                'name'       => $this->regPersonSaver->generateUniqueName($projectId,$user['name']),
            ];
            return $this->regPersonMapper->createFromArray2016($data);
        }
        // Cloning is a bitch
        $regPerson = $this->regPersonFinder->findByProjectPerson($projectIdPrevious,$personId);
        $regPerson->setFromArray([
            'regPersonId' => 0,
            'projectId'   => $projectId,
            'name'        => $this->regPersonSaver->generateUniqueName($projectId,$user['name']),
            'plans' => [],
            'avail' => [],
        ]);
        $fedPerson = $this->fedPersonFinder->find($regPerson->fedPersonId);
        if ($fedPerson) {
            $regPerson->setFromArray([
                'fedPersonId' => $fedPerson->fedPersonId,
                'fedOrgId'    => $fedPerson->fedOrgId,
                'regYear'     => $fedPerson->memYear,
            ]);
        }
        // Remove the roles
        foreach($regPerson->getRoles() as $role) {
            $regPerson->removeRole($role);
        }
        // Clone certs
        foreach($regPerson->getCerts() as $cert) {
            $cert->setFromArray([
                'regPersonRoleId' => 0,
                'regPersonId'     => 0,
            ]);
            if ($fedPerson) {
                $fedCert = isset($fedPerson->certs[$cert->role]) ? $fedPerson->certs[$cert->role] : null;
                if ($fedCert) {
                    $cert->setFromArray([
                        'roleDate'  => $fedCert->roleDate,
                        'badge'     => $fedCert->badge,
                        'badgeUser' => $fedCert->badge,
                        'badgeDate' => $fedCert->badgeDate,
                        'verified'  => $fedCert->isVerified,
                    ]);
                }
            }
            $regPerson->addCert($cert);
        }
        return $regPerson;
    }
    private function sendEmail(RegPerson $person) : void
    {
        $project  = $this->currentProject;
        $support  = $project->support;
        $assignor = $project->refAssignor;
        $refAdmin = $project->refAdmin;

        $update = $person->regPersonId ? ' Update' : null;

        $subject = sprintf('[%s] Registration%s for: %s',$project->abbv,$update,$person->name);

        $html = $this->templateEmail->renderHtml($person);

        $toms = [
            $refAdmin->email => $refAdmin->name, // Tom B
            $assignor->email => $assignor->name, // Tom T
        ];

        $mailer = $this->mailerLocator->getMailer();

        /** @var Swift_Message $message */
        $message = $mailer->createMessage();

        $message->setBody($html, 'text/html');

        $message->setSubject($subject);

        $message->setFrom(['noreply@zayso.org' => 'zAYSO Admin']);

        $message->setTo([$person->email => $person->name]);

        $message->setCc($toms);

        $message->setReplyTo($toms);

        $message->setBcc(
            [
                $support['email'] => $support['name'],
                'web.ng2019@gmail.com' => 'Rick Roberts', // ???
            ]
        );
        $mailer->send($message);
    }
}
