<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Project\User\ProjectUserRepository;

use Swift_Message;
use Symfony\Component\HttpFoundation\Request;

class PasswordResetRequestController extends AbstractController2
{
    /** @var  ProjectUserRepository */
    private $projectUserRepository;
    
    /** @var  PasswordResetRequestForm */
    private $form;
    
    public function __construct(
        ProjectUserRepository    $projectUserRepository,
        PasswordResetRequestForm $form
    )
    {
        $this->form = $form;
        $this->projectUserRepository = $projectUserRepository;
    }
    public function __invoke(Request $request)
    {
        $form = $this->form;

        $form->handleRequest($request);

        if ($form->isValid()) {

            $formData = $form->getData();

            $user = $this->projectUserRepository->find($formData['identifier']);

            $user['passwordToken'] = $this->projectUserRepository->generateToken();
            $user = $this->projectUserRepository->save($user);

            $this->sendEmail($user);

            return $this->redirectToRoute('user_password_reset_response');
        }
        return null;
    }
    private function sendEmail($user)
    {
        $mailer = $this->getMailer();

        $users = [
            $user['email'] => $user['name']
        ];

        $admin = [
            'admin@zayso.org' => 'zAYSO Admin'
        ];

        $bcc = [
            'web.ng2019@gmail.com' => 'Rick Roberts'
        ];

        $subject = sprintf('[zAYSOAdmin] Password Reset Request for: %s', $user['name']);

        $token = $user['passwordToken'];

        $resetURL = $this->generateUrlAbsoluteUrl('user_password_reset_response', ['token' => $token]);

        $body = <<<EOD
A zAYSO password reset request has been made.

Your password reset token is: {$token}

Please enter this token on the site password reset confirmation page.

OR click here: 

{$resetURL}
EOD;
        /** @var Swift_Message $message */
        $message = $mailer->createMessage();

        $message->setTo($users);

        $message->setFrom($admin);

        $message->setBcc($bcc);

        $message->setSubject($subject);

        $message->setBody($body);

        $mailer->send($message);
    }
}