<?php

namespace AppBundle\Action\GameOfficial\Workflow;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/* =========================================================
 * The actual workflow is encoded in a yaml file
 * 
 * TODO: Should be possible to have project specific workflows
 */
class AssignWorkflow
{
    /** @var  EventDispatcherInterface */
    protected $dispatcher;
    
    protected $assignStateAbbreviations;
    
    public function __construct()
    {
        $configFilePath = __DIR__ . '/assign.yml';
        
        $config = Yaml::parse(file_get_contents($configFilePath));
        
        $this->assignStateAbbreviations  = $config['assignStateAbbreviations'];
        $this->assigneeStateTransitions  = $config['assigneeStateTransitions'];
        $this->assignorStateTransitions  = $config['assignorStateTransitions'];
        
        // Bi directional mappings between what the application uses for states
        // And what the actual tables have
        // Really need this? Makes the YAML file easier to understand
        $this->mapInternalToPostedStates = $config['assignStateMap'];
        
        $map = [];
        foreach($this->mapInternalToPostedStates as $key => $value) {
            $map[$value] = $key;
        }
        $this->mapPostedToInternalStates = $map;
    }
    public function setDispatcher(EventDispatcherInterface $dispatcher) 
    { 
        $this->dispatcher = $dispatcher; 
    }
    
    public function getAssignStateAbbreviations() 
    { 
        return $this->assignStateAbbreviations; 
    }
    
    public function mapInternalStateToPostedState($state)
    {
        if (isset( $this->mapInternalToPostedStates[$state])) {
            return $this->mapInternalToPostedStates[$state];
        }
        return 'Open';
    }
    public function mapPostedStateToInternalState($state)
    {
        if (isset( $this->mapPostedToInternalStates[$state])) {
            return $this->mapPostedToInternalStates[$state];
        }
        return 'StateOpen';
    }
    protected function getStateOptions($state,$transitions)
    {
        $state = $this->mapPostedStateToInternalState($state);
        
        $items = $transitions[$state];
        $options = [];
        foreach($items as $state => $item)
        {   
            $state = $this->mapInternalStateToPostedState($state);   
            $options[$state] = $item['desc'];
        }
        return $options;
    }
    // Mark as abstract
    public function process($project,$gameOfficialOrg,$gameOfficialNew, $projectOfficial) {}   
 }