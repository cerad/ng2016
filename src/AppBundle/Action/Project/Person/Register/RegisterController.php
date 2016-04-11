<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Doctrine\DBAL\Connection;

class RegisterController extends AbstractController
{
    private $conn;
    private $registerForm;
    
    public function __construct(
        Connection   $conn,
        RegisterForm $registerForm
    )
    {
        $this->conn = $conn;
        $this->registerForm = $registerForm;
    }
    public function __invoke(Request $request)
    {
        $project = $this->getCurrentProject();
        $projectKey = $project['info']['key'];

        $user = $this->getUser();
        $personKey = $user['personKey'];

        $projectPerson = $this->findProjectPerson($projectKey,$personKey);
        if (!$projectPerson) {
            $this->insertProjectPerson($projectKey,$personKey,$user['name'],$user['email']);
            $projectPerson = $this->findProjectPerson($projectKey,$personKey);
        }

        $registerForm = $this->registerForm;
        $registerForm->setData($projectPerson);
        $registerForm->handleRequest($request);
        if ($registerForm->isValid()) {
            
        }
        $request->attributes->set('registerForm', $registerForm);
        $request->attributes->set('projectPerson',$projectPerson);
        
        return null;
    }
    private function findProjectPerson($projectKey,$personKey)
    {
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'projectPerson.id         AS id',
            'projectPerson.projectKey AS projectKey',
            'projectPerson.personKey  AS personKey',
            'projectPerson.name       AS name',
            'projectPerson.email      AS email',
        ]);
        $qb->from ('project_persons','projectPerson');
        $qb->where('projectPerson.projectKey = :projectKey AND projectPerson.personKey = :personKey');
        $qb->setParameters([
            'projectKey' => $projectKey,
            'personKey'  => $personKey,
        ]);
        $row = $qb->execute()->fetch();
        return $row;
    }
    private function insertProjectPerson($projectKey,$personKey,$name,$email)
    {
        // TODO ensure name is unique within a project
        
        $qb = $this->conn->createQueryBuilder();
        $qb->insert('project_persons');
        $qb->values([
            'projectKey' => ':projectKey',
            'personKey'  => ':personKey',
            'name'       => ':name',
            'email'      => ':email',
        ]);
        $qb->setParameters([
            'projectKey' => $projectKey,
            'personKey'  => $personKey,
            'name'       => $name,
            'email'      => $email,
        ]);
        return $qb->execute();
    }
}
