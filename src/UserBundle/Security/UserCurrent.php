<?php
namespace Cerad\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Symfony\Component\EventDispatcher\Event as PersonFindEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Cerad\Bundle\PersonBundle\PersonEvents;

/* ==============================================================
 * Used to inject the current user into a service
 * No real need to proxy the security context or dispatcher
 * 
 * Only create the user once for now, 
 * Not sure if that will cause issues doen the line on user changes
 */
class UserCurrent
{
    protected $user;
    protected $userPerson;
    
    protected $dispatcher;
    protected $securityContext;
    
    public function __construct
    (
        SecurityContextInterface $securityContext, 
        EventDispatcherInterface $dispatcher
    )
    {
        $this->dispatcher      = $dispatcher;
        $this->securityContext = $securityContext;
    }
    public function getUser()
    {
        if ($this->user) return $this->user;
        
        $token = $this->securityContext->getToken();
        if (!$token) return null;

        $user = $token->getUser();
        if (!is_object($user)) return null;
        
        return $this->user = $user;
    }
    public function getUserPerson()
    {
        if ($this->userPerson) return $this->userPerson;
        
        $user = $this->getUser();
        if (!$user) return null;
        
        $guid = $user->getPersonGuid();
        if (!$guid) return;
        
        $event = new PersonFindEvent;
        $event->guid   = $guid;
        $event->person = null;
        
        $this->dispatcher->dispatch(PersonEvents::FindPersonByGuid,$event);
        
        $userPerson = $event->person;
        if (!$userPerson) return null;
        
        $userPerson->setUser($user);
        
        return $this->userPerson = $userPerson;
    }
}
?>
