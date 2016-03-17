<?php
namespace Cerad\Bundle\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Cerad\Bundle\UserBundle\Model\UserInterface as UserModelInterface;
use Cerad\Bundle\UserBundle\Model\UserManager   as UserModelManager;

use Cerad\Bundle\UserBundle\Entity\UserRepository as UserEntityRepository;

class UserManager extends UserModelManager
{       
    protected $userPepository;
    
    public function __construct
    (
        EncoderFactoryInterface $encoderFactory,
        UserEntityRepository    $userRepository
    )
    {
        parent::__construct($encoderFactory);
        $this->userRepository = $userRepository;
    }
    public function createUser()
    {
        return $this->userRepository->createUser();
    }
    public function updateUser(UserModelInterface $user, $commit = true)
    {
        parent::updateUser($user,$commit);
        
        $this->userRepository->save($user);
        
        if ($commit) $this->userRepository->commit();
    }
    public function findUser($id)
    {
        return $this->userRepository->find($id);
    }
    public function findUsers()
    {
        return $this->userRepository->findAll();
    }
    public function findUserByEmail($email)
    {
        $email = $this->canonicalizeEmail($email);
        
        return $this->userRepository->findOneBy(array('emailCanonical' => $email));
    }
    public function findUserByUsername($username)
    {
        $username = $this->canonicalizeUsername($username);
        
        return $this->userRepository->findOneBy(array('usernameCanonical' => $username));
    }
    public function findUserByUsernameOrEmail($search)
    {
        $user1 = $this->findUserByEmail($search);
        if ($user1) return $user1;
        
        $user2 = $this->findUserByUsername($search);
        if ($user2) return $user2;
        
        return null;
    }
    public function findUserByPersonGuid($personGuid)
    {
       return $this->userRepository->findOneBy(array('personGuid' => $personGuid));
    }
}
?>
