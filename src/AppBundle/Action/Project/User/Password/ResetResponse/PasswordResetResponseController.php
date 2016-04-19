<?php
namespace AppBundle\Action\Project\User\Password\ResetResponse;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\User\ProjectUserEncoder;
use AppBundle\Action\Project\User\ProjectUserLoginUser;
use AppBundle\Action\Project\User\ProjectUserProvider;
use AppBundle\Action\Project\User\ProjectUserRepository;

use Symfony\Component\HttpFoundation\Request;

class PasswordResetResponseController extends AbstractController
{
    /** @var  ProjectUserEncoder */
    private $userEncoder;
    
    /** @var  ProjectUserRepository */
    private $userRepository;

    /** @var ProjectUserProvider  */
    private $userProvider;

    /** @var ProjectUserLoginUser  */
    private $userLoginUser;

    /** @var  PasswordResetResponseForm */
    private $form;
    
    /** @var  string */
    private $successRouteName;
    
    public function __construct(
        ProjectUserEncoder        $userEncoder,
        ProjectUserProvider       $userProvider,
        ProjectUserLoginUser      $userLoginUser,
        ProjectUserRepository     $userRepository,
        PasswordResetResponseForm $form,
                                  $successRouteName
   )
    {
        $this->form             = $form;
        $this->userEncoder      = $userEncoder;
        $this->userProvider     = $userProvider;
        $this->userLoginUser    = $userLoginUser;
        $this->userRepository   = $userRepository;
        $this->successRouteName = $successRouteName;
    }
    public function __invoke(Request $request, $token)
    {
        $form = $this->form;
        
        $form->setData(['token' => $token]);
        
        $form->handleRequest($request);

        if ($form->isValid()) {

            $formData = $form->getData();

            $user = $this->userRepository->find($formData['token']);
            
            $user['password']      = $this->userEncoder->encodePassword($formData['password'],$user['salt']);
            $user['passwordToken'] = null;
            $user['emailVerified'] = true; // Maybe send an event
            
            $user = $this->userRepository->save($user);
            
            $user = $this->userProvider->loadUserByUsername($user['username']);
            
            $this->userLoginUser->loginUser($request,$user);
            
            return $this->redirectToRoute($this->successRouteName);
        }
        return null;
    }
}