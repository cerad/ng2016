<?php

namespace AppBundle\Action\GameOfficial;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AssignWorkflow
{
    /** @var  EventDispatcherInterface */
    protected $dispatcher;
    
    public $assignStateAbbreviations;
    public $assigneeStateTransitions;
    public $assignorStateTransitions;

    private $mapInternalToPostedStates;
    private $mapPostedToInternalStates;

    public function __construct()
    {
        $configFilePath = __DIR__ . '/config/assign.yml';
        
        $config = Yaml::parse(file_get_contents($configFilePath));
        
        $this->assignStateAbbreviations  = $config['assignStateAbbreviations'];
        $this->assigneeStateTransitions  = $config['assigneeStateTransitions'];
        $this->assignorStateTransitions  = $config['assignorStateTransitions'];

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
    public function getStateChoices($state,$transitions)
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
    //public function process($project,$gameOfficialOrg,$gameOfficialNew, $projectOfficial) {}
 }