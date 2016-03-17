<?php
namespace Cerad\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Psr\Log\LoggerInterface;

use Cerad\Bundle\UserBundle\Entity\User;
use Cerad\Bundle\UserBundle\Entity\UserRepository;


class UserProvider implements UserProviderInterface
{
    protected $logger;
    protected $dispatcher;
    protected $repository;
   
    public function __construct
    (
        UserRepository $repository,
        EventDispatcherInterface $dispatcher = null, 
        LoggerInterface $logger = null
    )
    {
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
    }
    public function loadUserByUsername($username)
    {
        $user = $this->repository->findOneBy(array('emailCanonical' => $username));
        if ($user) return $user;

        $user = $this->repository->findOneBy(array('usernameCanonical' => $username));
        if ($user) return $user;
        
        // Check for social network identifiers
        
        // See if a fed person exists
        /*
        $event = new FindPersonEvent($username);
        
        $this->dispatcher->dispatch(FindPersonEvent::FindByFedKeyEventName,$event);
        
        $person = $event->getPerson();
        if ($person)
        {
            $user = $this->userManager->findUserByPersonGuid($person->getGuid());
            if ($user) return $user;
        }
        */
        // Bail
        throw new UsernameNotFoundException('User Not Found: ' . $username);
    }

    public function refreshUser(UserInterface $user)
    {
        $userClass = User::class;
        if (!($user instanceOf $userClass)) {
            throw new UnsupportedUserException();
        }
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->repository->find($user->getId());
    }
    public function supportsClass($class)
    {
        $userClass = User::class;
        return ($class instanceOf $userClass) ? true: false;
    }
}
?>
