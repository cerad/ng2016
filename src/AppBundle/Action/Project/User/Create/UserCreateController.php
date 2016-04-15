<?php
namespace AppBundle\Action\Project\User\Create;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\User\ProjectUserEncoder;
use AppBundle\Action\Project\User\ProjectUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Doctrine\DBAL\Connection;

class UserCreateController extends AbstractController
{
    private $conn;
    private $userEncoder;
    private $userProvider;
    private $userCreateForm;
    
    public function __construct(
        Connection          $conn,
        ProjectUserProvider $userProvider,
        ProjectUserEncoder  $userEncoder,
        UserCreateForm      $userCreateForm
    )
    {
        $this->conn           = $conn;
        $this->userEncoder    = $userEncoder;
        $this->userProvider   = $userProvider;
        $this->userCreateForm = $userCreateForm;
    }
    public function __invoke(Request $request)
    {
        $userData = [
            'name'     =>  null,
            'email'    =>  null,
            'password' =>  null,
            'role'     => 'ROLE_USER',
        ];
        $userCreateForm = $this->userCreateForm;
        $userCreateForm->setData($userData);
        
        $userCreateForm->handleRequest($request);
        if ($userCreateForm->isValid()) {

            $userData = $userCreateForm->getData();

            $user = $this->createUser(
                $userData['name'],
                $userData['email'],
                $userData['password'],
                $userData['role']
            );
            $this->loginUser($request,$user);

            return $this->redirectToRoute('project_person_register');
        }
        $request->attributes->set('userCreateForm',$userCreateForm);
        
        return null;
    }
    private function createUser($name,$email,$password,$role)
    {
        // Encode password
        $salt     = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $password = $this->userEncoder->encodePassword($password,$salt);

        // Derive username from email?
        $emailParts = explode('@',$email);
        $username  = $emailParts[0];
        $username = $this->generateUniqueUsername($username);

        // Person guid
        $personKey = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0,     65535), mt_rand(0,     65535), mt_rand(0, 65535),
            mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
            mt_rand(0,     65535), mt_rand(0,     65535)
        );

        // The insert
        $qb = $this->conn->createQueryBuilder();
        
        $qb->insert('users');

        $qb->values([
            'name'      => ':name',
            'email'     => ':email',
            'username'  => ':username',
            'personKey' => ':personKey',
            'salt'      => ':salt',
            'password'  => ':password',
            //'enabled'   => ':enabled',
            'roles'     => ':roles',
        ]);

        $qb->setParameters([
            'name'      => $name,
            'email'     => $email,
            'username'  => $username,
            'personKey' => $personKey,
            'salt'      => $salt,
            'password'  => $password,
            //'enabled'   => true,
            'roles'     => $role,
        ]);
        // TODO add try/catch
        $qb->execute();

        return $this->userProvider->loadUserByUsername($email);
    }
    private function loginUser(Request $request, UserInterface $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get("security.token_storage")->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $event);
    }
    private function generateUniqueUsername($username)
    {
        $sql = 'SELECT id FROM users WHERE username = ?';
        $stmt = $this->conn->prepare($sql);

        $cnt = 1;
        $usernameTry = $username;
        while(true) {
            $stmt->execute([$usernameTry]);
            if (!$stmt->fetch()) {
                return($usernameTry);
            }
            $cnt++;
            $usernameTry = $username . $cnt;
        }
        return null;
    }
}
