<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPersonRepository;
use Symfony\Component\HttpFoundation\Request;

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
        $projectPerson = $this->findProjectPersonForUser($this->getUser());
        
        $registerForm = $this->registerForm;
        $registerForm->setData($projectPerson);
        $registerForm->handleRequest($request);
        
        if ($registerForm->isValid()) {

            $projectPersonOriginal = $projectPerson;

            $projectPerson = $registerForm->getData();

            /** @noinspection PhpUnusedLocalVariableInspection */
            $projectPerson = $this->process($registerForm->getSubmit(),$projectPerson,$projectPersonOriginal);

            return $this->redirectToRoute('app_home');
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
        // Want to referee?
        $plans = $projectPerson['plans'];
        $willReferee = $plans['willReferee'] !== 'no' ? true : false;
        if ($willReferee) {
             $projectPersonRole =
                isset($projectPerson['roles']['ROLE_REFEREE']) ?
                      $projectPerson['roles']['ROLE_REFEREE'] :
                      $this->projectPersonRepository->createRole('ROLE_REFEREE');
            $projectPersonRole['projectPersonId'] = $projectPerson['id'];
            $cert = $this->aysoRepository->findVolCert($fedKey,'ROLE_REFEREE');
            if ($cert) {
                $projectPersonRole['roleDate']  = $cert['roleDate'];
                $projectPersonRole['badge']     = $cert['badge'];
                $projectPersonRole['badgeDate'] = $cert['badgeDate'];
                $projectPersonRole['verified']  = true;
            }
            $projectPerson['roles']['ROLE_REFEREE'] = $projectPersonRole;
        }
        // Need some notifications here?
        $projectPerson['registered'] = true;

        $this->projectPersonRepository->save($projectPerson,$projectPersonOriginal);

        return $projectPerson;
    }
    private function findProjectPersonForUser($user)
    {
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        // Existing
        $projectPerson = $this->projectPersonRepository->find($projectKey,$personKey);
        if ($projectPerson) {
            return $projectPerson;
        }
        // Clone from previous tournament
        $projectPerson = $this->projectPersonRepository->find('AYSONationalGames2014',$personKey);

        if (!$projectPerson) {
            return $this->projectPersonRepository->create($projectKey, $personKey, $user['name'], $user['email']);
        }
        $projectPerson['id'] = null;
        $projectPerson['projectKey'] = $projectKey;

        $fedKey = $projectPerson['fedKey'];

        $vol = $this->aysoRepository->findVol($fedKey);
        if ($vol) {
            dump($vol);
            $projectPerson['orgKey']  = $vol['orgKey'];
            $projectPerson['regYear'] = $vol['regYear'];
            $projectPerson['gender']  = $vol['gender'];
        }
        if (isset($projectPerson['age'])) {
            $projectPerson['age'] += 2;
        }
        // Update cert info
        foreach($projectPerson['roles'] as $roleKey => $projectPersonRole) {

            $projectPersonRole['id'] = null;

            $cert = $this->aysoRepository->findVolCert($fedKey,$roleKey);

            if ($cert) {
                $projectPersonRole['roleDate']  = $cert['roleDate'];
                $projectPersonRole['badge']     = $cert['badge'];
                $projectPersonRole['badgeDate'] = $cert['badgeDate'];
                $projectPersonRole['verified']  = true;
            }
            $projectPerson['roles'][$roleKey] = $projectPersonRole;
        }
        return $projectPerson;
    }
}
