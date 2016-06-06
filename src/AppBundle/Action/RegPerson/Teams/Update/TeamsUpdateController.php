<?php
namespace AppBundle\Action\RegPerson\Teams\Update;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\RegPerson\RegPersonFinder;

use AppBundle\Action\RegPerson\RegPersonUpdater;
use Symfony\Component\HttpFoundation\Request;

class TeamsUpdateController extends AbstractController2
{
    private $form;
    private $regPersonFinder;
    private $regPersonUpdater;
    
    public function __construct(
        TeamsUpdateForm  $form,
        RegPersonFinder  $regPersonFinder,
        RegPersonUpdater $regPersonUpdater

    ) {
        $this->form = $form;
        $this->regPersonFinder  = $regPersonFinder;
        $this->regPersonUpdater = $regPersonUpdater;
    }
    public function __invoke(Request $request)
    {
        $managerId = $this->getUser()->getRegPersonId();

        $regPersonTeams = $this->regPersonFinder->findRegPersonTeams($managerId);
        
        $form = $this->form;
        $form->setRegPersonTeams($regPersonTeams);
        
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            
            $addTeamId = $form->getRegPersonTeamAdd();
            if ($addTeamId) {
                $this->regPersonUpdater->addRegPersonTeam($managerId,$addTeamId);
            }
            $removeTeamIds = $form->getRegPersonTeamsRemove();
            foreach($removeTeamIds as $removeTeamId) {
                if ($removeTeamId) {
                    list($role, $teamId) = explode(' ', $removeTeamId);
                    $this->regPersonUpdater->removeRegPersonTeam($managerId,$teamId,$role);
                }
            }
            return $this->redirectToRoute('reg_person_teams_update');
        }
        return null;
    }
}
