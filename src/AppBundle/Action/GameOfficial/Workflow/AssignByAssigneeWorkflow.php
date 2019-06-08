<?php

namespace AppBundle\Action\GameOfficial\Workflow;

use AppBundle\Action\Game\GameOfficial;
use GameBundle\GameEvents;
use GameBundle\Event\GameOfficial\AssignSlotEvent;

use CoreBundle\Event\Person\FindProjectPersonEvent;

class AssignByAssigneeWorkflow extends AssignWorkflow
{
    public function getStateOptions($state, $transitions = null)
    {
        return parent::getStateOptions($state,$this->assigneeStateTransitions);
    }
    /* =========================
     * Returns false if unchanged
     */
    public function process(
        $project,
        GameOfficial $gameOfficialOrg,
        GameOfficial $gameOfficialNew,
        $projectPerson
    ) {   
        $assignStateNew = $this->mapPostedStateToInternalState($gameOfficialNew->assignState);
        $assignStateOrg = $this->mapPostedStateToInternalState($gameOfficialOrg->assignState);
        
        $personKeyNew = $gameOfficialNew->regPersonId;
        $personKeyOrg = $gameOfficialOrg->regPersonId;
        
        // Should always have one before getting here
        if (!$personKeyNew) return false;
        
        // Changed the person then should change the info
        if ($personKeyNew != $personKeyOrg)
        {
            $event = new FindProjectPersonEvent($project,$personKeyNew);
            $this->dispatcher->dispatch(FindProjectPersonEvent::ByGuid,$event);
            $person = $event->getPerson();
            if (!$person) return false;
            $gameOfficialNew->changePerson($person);
        }
        // Neither key not state changed
        else if ($assignStateNew == $assignStateOrg) return false;
        
        $transition = $this->assigneeStateTransitions[$assignStateOrg][$assignStateNew];
        
        // Normally go directly to new state but sometimes want a different state
        $assignStateMod = isset($transition['modState']) ? $transition['modState'] : $assignStateNew;
        if ($assignStateMod != $assignStateNew)
        {
            $gameOfficialNew->setAssignState($this->mapInternalStateToPostedState($assignStateMod));
        }
        // Transfer or clear person
        switch($assignStateMod)
        {
            case 'StateOpen':
                $gameOfficialNew->changePerson(null);
                break;
            default:
              //$gameOfficialNew->setPersonFromPlan($projectOfficial);
        }
        // Notify the world
        $event = new AssignSlotEvent;
        $event->project         = $project;
        $event->gameOfficial    = $gameOfficialNew;
        $event->gameOfficialOrg = $gameOfficialOrg;
        $event->command         = $assignStateNew;
        $event->workflow        = $this;
        $event->transition      = $transition;
        $event->by              = 'Assignee';
        
      //$this->dispatcher->dispatch(GameEvents::GameOfficialAssignSlot,$event);
        
        return true;
    }
}