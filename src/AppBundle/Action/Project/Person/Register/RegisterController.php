<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPersonRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use Doctrine\DBAL\Connection;

class RegisterController extends AbstractController
{
    private $registerForm;
    private $aysoRepository;
    private $projectPersonRepository;

    public function __construct(
        RegisterForm            $registerForm,
        PhysicalAysoRepository  $aysoRepository,
        ProjectPersonRepository $projectPersonRepository
    )
    {
        $this->registerForm            = $registerForm;
        $this->aysoRepository          = $aysoRepository;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request)
    {
        $user = $this->getUser();
        
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        $projectPersonRepository = $this->projectPersonRepository;

        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        if (!$projectPerson) {
            $projectPerson = $projectPersonRepository->create($projectKey,$personKey,$user['name'],$user['email']);
        }
        dump($projectPerson);
        
        $registerForm = $this->registerForm;
        $registerForm->setData($projectPerson);
        $registerForm->handleRequest($request);
        
        if ($registerForm->isValid()) {

            $projectPersonOriginal = $projectPerson;

            $projectPerson = $registerForm->getData();

            $projectPerson = $this->process($registerForm->getSubmit(),$projectPerson,$projectPersonOriginal);

            return $this->redirectToRoute('project_person_register');
        }
        $request->attributes->set('registerForm', $registerForm);
        $request->attributes->set('projectPerson',$projectPerson);
        
        return null;
    }
    private function process($submit,$projectPerson,$projectPersonOriginal)
    {
        $fedKey = $projectPerson['fedKey'];

        $vol = $this->aysoRepository->findVol($fedKey);
        if ($vol) {
            $projectPerson['orgKey']  = $vol['orgKey'];
            $projectPerson['regYear'] = $vol['regYear'];
            $projectPerson['gender']  = $vol['gender'];
        }
        //dump($projectPerson);
        if ($submit == 'nope') {

            $projectPerson['registered'] = false;
            $projectPerson['verified']   = null;

            $this->projectPersonRepository->save($projectPerson,$projectPersonOriginal);

            return $projectPerson;
        }
        // Need some notifications here?
        $projectPerson['registered'] = true;

        $this->projectPersonRepository->save($projectPerson,$projectPersonOriginal);

        return $projectPerson;
    }
}
