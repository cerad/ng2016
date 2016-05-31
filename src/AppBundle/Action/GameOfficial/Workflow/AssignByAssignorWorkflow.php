<?php

namespace Cerad\Bundle\GameBundle\Action\Project\GameOfficials\Assign;

use Cerad\Bundle\CoreBundle\Event\Person\FindProjectPersonEvent;

class AssignByAssignorWorkflow extends AssignWorkflow
{
    public function getStateOptions($state, $options = null)
    {
        return parent::getStateOptions($state,$this->assignorStateTransitions);
        if ($options);
    }
    protected function findProjectPerson($project,$gameOfficial)
    {
        if ($gameOfficial->getPersonGuid())
        {
            $event = new FindProjectPersonEvent($project,$gameOfficial->getPersonGuid());
            $this->dispatcher->dispatch(FindProjectPersonEvent::ByGuid,$event);
            return $event->getPerson();
        }
        if ($gameOfficial->getPersonNameFull())
        {
            $event = new FindProjectPersonEvent($project,$gameOfficial->getPersonNameFull());
            $this->dispatcher->dispatch(FindProjectPersonEvent::ByName,$event);
            return $event->getPerson();
        }
        return null;
    }
    /* ================================================================
     * New or changed assignment
     */
    public function assign($project,$orgGameOfficial,$newGameOfficial)
    {
        // There are three things that can change, guid, state, name
        $changed = false;
        
        $orgGuid = $orgGameOfficial->getPersonGuid();
        $newGuid = $newGameOfficial->getPersonGuid();
        
        $orgName = $orgGameOfficial->getPersonNameFull();
        $newName = $newGameOfficial->getPersonNameFull();
        
        $orgState = $this->mapPostedStateToInternalState($orgGameOfficial->getAssignState());
        $newState = $this->mapPostedStateToInternalState($newGameOfficial->getAssignState());
        
        if ($orgGuid  != $newGuid)  { $changed = true; }
        if ($orgName  != $newName)  { $changed = true; }
        if ($orgState != $newState) { $changed = true; }
        if (!$changed) return;
        
        // Something has changed
        $projectPerson = $this->findProjectPerson($project,$newGameOfficial);
        $newGameOfficial->changePerson($projectPerson);
        
        // If no guid but name then just the name was types in
        if ($newName && !$newGuid)
        {
            $newGameOfficial->setPersonNameFull($newName);
        }
        // If guid or name then set default state
        if (!$newName || $newGuid)
        {
            if ($newState == 'StateOpen')
            {
                $newState = 'StatePendingByAssignor';
                
                $newGameOfficial->setAssignState($this->mapInternalStateToPostedState($newState));
            }
        }
        // Done if no state change
        if ($newState == $orgState)
        {
            // TODO: Notify if person was changed
            return;
        }
        // Deal with a state changed
        $transition = $this->assignorStateTransitions[$orgState][$newState];
        
        // Normally go directly to new state but sometimes want a different state
        $modState = isset($transition['modState']) ? $transition['modState'] : $newState;
        if ($modState != $newState)
        {
            $newGameOfficial->setAssignState($this->mapInternalStateToPostedState($modState));
        }
        // Transfer or clear person
        if ($modState == 'StateOpen')
        {
            $newGameOfficial->changePerson(null);
        }
        // Should we notify the assiignee
        $notifyAssignee = isset($transition['notifyAssignee']) ? true : false;
        
        if (!$notifyAssignee) return;
    }
    /* ==============================================
     * TODO: Make a bit cleaner and prevent state errors
     */
    public function process($project,$gameOfficialOrg,$gameOfficialNew,$xxx)
    {   
        // AR with person guid or name
        $projectPerson = $this->findProjectPerson($project,$gameOfficialNew);
        
        // Save this since the assignor may have types in a name
        $personNameNew = $gameOfficialNew->getPersonNameFull();
        
        // Update with new info
        $gameOfficialNew->changePerson($projectPerson);
        
        $assignStateNew = $this->mapPostedStateToInternalState($gameOfficialNew->getAssignState());
        $assignStateOrg = $this->mapPostedStateToInternalState($gameOfficialOrg->getAssignState());
        
        // The assignor can type directly into the name
        if (!$projectPerson && $personNameNew)
        {
            $gameOfficialNew->setPersonNameFull($personNameNew);
            if ($assignStateNew == 'StateOpen')
            {
                $assignStateNew = 'StatePendingByAssignor';
                $gameOfficialNew->setAssignState($this->mapInternalStateToPostedState($assignStateNew));
            }
            // No need to worry about notifications here
            return;
        }
        
        // Hack
        if ($gameOfficialNew->getPersonGuid() && $assignStateNew == 'StateOpen')
        {
            $assignStateNew = 'StatePendingByAssignor';
            $gameOfficialNew->setAssignState($this->mapInternalStateToPostedState($assignStateNew));
        }
        // TODO: send a message of the assigned person changed
        if ($assignStateNew == $assignStateOrg) 
        {
            if ($gameOfficialNew->getPersonGuid() != $gameOfficialOrg->getPersonGuid())
            {
                // Notify person was changed
            }
            return;
        }
        // Deal with a state changed
        $transition = $this->assignorStateTransitions[$assignStateOrg][$assignStateNew];
        
        // Normally go directly to new state but sometimes want a different state
        $assignStateMod = isset($transition['modState']) ? $transition['modState'] : $assignStateNew;
        if ($assignStateMod != $assignStateNew)
        {
            $gameOfficialNew->setAssignState($this->mapInternalStateToPostedState($assignStateMod));
        }
        // Transfer or clear person
        if ($assignStateMod == 'StateOpen')
        {
            $gameOfficialNew->changePerson(null);
        }
        // Should we notify the assiignee
        $notifyAssignee = isset($transition['notifyAssignee']) ? true : false;
        
        if (!$notifyAssignee) return;
        
        // TODO: - Kick off notification
    }
}