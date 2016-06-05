<?php
namespace AppBundle\Action\RegPerson\Persons\Update;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\RegPerson\RegPersonFinder;

use AppBundle\Action\RegPerson\RegPersonUpdater;
use Symfony\Component\HttpFoundation\Request;

class PersonsUpdateController extends AbstractController2
{
    private $form;
    private $regPersonFinder;
    private $regPersonUpdater;
    
    public function __construct(
        PersonsUpdateForm $form,
        RegPersonFinder   $regPersonFinder,
        RegPersonUpdater  $regPersonUpdater

    ) {
        $this->form = $form;
        $this->regPersonFinder  = $regPersonFinder;
        $this->regPersonUpdater = $regPersonUpdater;
    }
    public function __invoke(Request $request)
    {
        $regPersonPersons = $this->regPersonFinder->findRegPersonPersons($this->getUser()->getRegPersonId());
        
        $form = $this->form;
        $form->setRegPersonPersons($regPersonPersons);
        
        $form->handleRequest($request);
        
        $managerId = $this->getUser()->getRegPersonId();
        
        if ($form->isValid()) {
            $regPersonPersonAdd = $form->getRegPersonPersonAdd();
            if ($regPersonPersonAdd) {
                
                $this->regPersonUpdater->addRegPersonPerson(
                    $regPersonPersonAdd['role'],
                    $managerId,
                    $regPersonPersonAdd['memberId']);
            }
            $removeIds = $form->getRegPersonPersonsRemove();
            foreach($removeIds as $removeId) {
                list($role,$memberId) = explode(' ',$removeId);
                $this->regPersonUpdater->removeRegPersonPerson($role,$managerId,$memberId);
            }
            return $this->redirectToRoute('reg_person_persons_update');
        }
        return null;
    }
}
