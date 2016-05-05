<?php
namespace AppBundle\Action\User;

use AppBundle\Action\Project\User\ProjectUserRepository;
use AppBundle\Common\GuidGeneratorTrait;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserManager
{
    private $conn;
    private $encoder;
    private $password;
    private $repository;

    public function __construct(
        Connection $conn,
        PasswordEncoderInterface $encoder,
        ProjectUserRepository $repository,
        $password
    )
    {
        $this->conn       = $conn;
        $this->encoder    = $encoder;
        $this->password   = $password;
        $this->repository = $repository;
    }
    public function findUser($identifier)
    {
        $sql  = 'SELECT * FROM users WHERE email = ?';
        $stmt = $this->conn->executeQuery($sql,[$identifier]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        $xfer = [
            'id'          => 'userId',
            'personKey'   => 'personId',
            'providerKey' => 'providerId',
        ];
        foreach($xfer as $from => $to) {
            $user[$to] = $user[$from];
            unset($user[$from]);
        }
        $user['projectId'] = null;

        return $user;
    }
    use GuidGeneratorTrait;
    public function createUser($email, $name, $username = null, $password = null, $personId = null)
    {
        // Validate email
        if (!$this->repository->isEmailUnique($email)) {
            throw new \Exception('UserManager::createUser email already exists ' . $email);
        }
        // Build a username if needed
        if ($username) {
            $username = $this->repository->generateUniqueUsername($username);
        }
        else {
            $username = $this->repository->generateUniqueUsernameFromEmail($email);
        }
        // New person
        if (!$personId) {
            $personId = $this->generateGuid();
        }
        // Encode the stuff
        $salt     = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $password = $password ? : $this->password;
        $password = $this->encoder->encodePassword($password,$salt);

        $user = [
            'name'     => $name,
            'email'    => $email,
            'username' => $username,
            'personId' => $personId,
            'salt'     => $salt,
            'password' => $password,
            'roles'    => 'ROLE_USER',
        ];
        return $user;
    }
    public function saveUser($user)
    {
        $user['personKey'] = $user['personId'];
        unset($user['personId']);

        // Just insert for now
        $this->conn->insert('users',$user);
        $user['userId'] = $this->conn->lastInsertId();

        $user['personId'] = $user['personKey'];
        unset($user['personKey']);
        
        return $user;
    }
}