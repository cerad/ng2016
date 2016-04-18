<?php
namespace AppBundle\Action\Project\User;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class ProjectUserLoginUser
{
    /** @var  EventDispatcherInterface */
    private $eventDispatcher;
    
    /** @var  TokenStorageInterface */
    private $securityTokenStorage;

    private $firewallName; // main
    
    public function __construct(
        $firewallName,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface    $securityTokenStorage
    )
    {
        $this->firewallName          = $firewallName;
        $this->eventDispatcher       = $eventDispatcher;
        $this->securityTokenStorage  = $securityTokenStorage;
    }
    public function loginUser(Request $request, UserInterface $user)
    {
        $token = new UsernamePasswordToken($user, null, $this->firewallName, $user->getRoles());
        
        $this->securityTokenStorage->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        
        $this->eventDispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $event);
    }
}
