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
        Connection $conn,
        ProjectUserProvider $userProvider,
        ProjectUserEncoder  $userEncoder,
        UserCreateForm $userCreateForm
    )
    {
        $this->conn = $conn;
        $this->userEncoder    = $userEncoder;
        $this->userProvider   = $userProvider;
        $this->userCreateForm = $userCreateForm;
    }
    public function __invoke(Request $request)
    {
        $userData = [
            'name'     => null,
            'email'    => null,
            'password' => null,
        ];
        $userCreateForm = $this->userCreateForm;
        $userCreateForm->setData($userData);
        
        $userCreateForm->handleRequest($request);
        if ($userCreateForm->isValid()) {

            $userData = $userCreateForm->getData();

            $user = $this->createUser($userData['name'],$userData['email'],$userData['password']);
            $this->loginUser($request,$user);

            return $this->redirectToRoute('app_home');
        }
        $request->attributes->set('userCreateForm',$userCreateForm);
        
        return null;
    }
    private function createUser($name,$email,$password)
    {
        // Encode password
        $salt     = $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $password = $this->userEncoder->encodePassword($password,$salt);

        // Derive username from email?

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
            'username'           => '?',
            'username_canonical' => '?',
            'email'              => '?',
            'email_canonical'    => '?',

            'salt'     => '?',
            'password' => '?',
            'roles'    => '?',

            'account_name'    => '?',
            'account_enabled' => '?',
            'person_guid'     => '?',
        ]);
        $qb->setParameters([
            0 => $email,
            1 => $email,
            2 => $email,
            3 => $email,
            4 => $salt,
            5 => $password,
            6 => serialize(['ROLE_USER']),
            7 => $name,
            8 => true,
            9 => $personKey,
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
}