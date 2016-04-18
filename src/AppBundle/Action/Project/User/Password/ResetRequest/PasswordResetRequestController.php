<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\User\ProjectUserRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetRequestController extends AbstractController
{
    /** @var \Swift_Mailer  */
    private $mailer;

    /** @var  ProjectUserRepository */
    private $projectUserRepository;
    
    /** @var  PasswordResetRequestForm */
    private $form;
    
    public function __construct(
        \Swift_Mailer $mailer,
        ProjectUserRepository $projectUserRepository,
        PasswordResetRequestForm $form
    )
    {
        $this->form   = $form;
        $this->mailer = $mailer;
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
        $token = $user['passwordToken'];

        $subject = sprintf('[ZaysoAdmin] Password Reset Request for: %s',$user['name']);

        $body = <<<EOD
A Zayso password reset request has been made.

Your password reset token is: {$token}

Please enter this token on the site password reset confirmation page.

OR click here: 

{$this->generateUrl('user_password_reset_response',['token' => $token],UrlGeneratorInterface::ABSOLUTE_URL)}
EOD;
        $message = $this->mailer->createMessage();

        $message->setBody($body);

        $message->setSubject($subject);

        $message->setFrom(['noreply@zayso.org' => 'Zayso Admin']);

        $message->setTo([$user['email'] => $user['name']]);

        $message->setBcc(['ahundiak@gmail.com' => 'Art Hundiak']);

        /** @noinspection PhpParamsInspection */
        $this->mailer->send($message);
    }
}