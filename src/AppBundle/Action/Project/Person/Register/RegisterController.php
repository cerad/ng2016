<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\Person\ProjectPersonRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Doctrine\DBAL\Connection;

class RegisterController extends AbstractController
{
    private $conn;
    private $projectKey;
    private $registerForm;
    private $projectPersonRepository;

    public function __construct(
        Connection   $conn,
        ProjectPersonRepository $projectPersonRepository,
        RegisterForm $registerForm,
        $projectKey
    )
    {
        $this->conn         = $conn;
        $this->projectKey   = $projectKey;
        $this->registerForm = $registerForm;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request)
    {
        $projectKey = $this->projectKey;

        $user = $this->getUser();
        $personKey = $user['personKey'];

        $projectPersonRepository = $this->projectPersonRepository;

        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->create($projectKey,$personKey,$user['name'],$user['email']);
        }

        $registerForm = $this->registerForm;
        $registerForm->setData($projectPerson);
        $registerForm->handleRequest($request);
        
        if ($registerForm->isValid()) {
            $projectPersonOriginal = $projectPerson;
            $projectPerson = $registerForm->getData();
            //dump($projectPerson);
            if ($registerForm->getSubmit() == 'nope') {
                $projectPerson['registered'] = false;
                $projectPerson['verified']   = null;
                $projectPersonRepository->save($projectPerson);
                return $this->redirectToRoute('app_home');
            }
            // Need some notifications here
            $projectPerson['registered'] = true;
            $projectPersonRepository->save($projectPerson,$projectPersonOriginal);

            return $this->redirectToRoute('project_person_register');
        }
        $request->attributes->set('registerForm', $registerForm);
        $request->attributes->set('projectPerson',$projectPerson);
        
        return null;
    }
    private function insertProjectPerson($projectKey,$personKey,$name,$email)
    {
        // TODO ensure name is unique within a project
        
        $qb = $this->conn->createQueryBuilder();
        $qb->insert('projectPersons');
        $qb->values([
            'projectKey' => ':projectKey',
            'personKey'  => ':personKey',
            'name'       => ':name',
            'email'      => ':email',
            'registered' => ':registered',
        ]);
        $qb->setParameters([
            'projectKey' => $projectKey,
            'personKey'  => $personKey,
            'name'       => $name,
            'email'      => $email,
            'registered' => false,
        ]);
        return $qb->execute();
    }
}
