<?php
namespace AppBundle\Action\Game;

class PoolTeam
{
    public $poolTeamId;
    public $projectId;
    
    public $poolKey;
    public $poolTypeKey;
    public $poolTeamKey;
    
    public $poolView;
    public $poolSlotView;
    
    public $poolTypeView;
    
    public $poolTeamView;
    public $poolTeamSlotView;
    
    public $sourcePoolKeys;
    public $sourcePoolSlot;
    
    public $program;
    public $gender;
    public $age;
    public $division;
    
    public $regTeamId;
    public $regTeamName;
    public $regTeamPoints;
    
    private $keys = [
        'poolTeamId'  => 'PoolTeamId',
        'projectId'   => 'ProjectId',
        
        'poolKey'     => 'PoolKey',
        'poolTypeKey' => 'PoolTeamKey',
        'poolTeamKey' => 'PoolTeamKey',

        'poolView'     => 'string',
        'poolSlotView' => 'string',
        
        'poolTypeView'     => 'string',
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',
    
        'sourcePoolKeys' => 'string',
        'sourcePoolSlot' => 'integer',

        'program'  => 'ProgramId',
        'gender'   => 'GenderId',
        'age'      => 'AgeId',
        'division' => 'DivisionId',
        
        'regTeamId'     => 'RegTeamId',
        'regTeamName'   => 'string',
        'regTeamPoints' => 'integer',
    ];
    public function __get($name)
    {
        switch($name) {
            case 'extraPoints':
                if ($this->poolTypeKey !== 'PP') {
                    return null;
                }
                return $this->regTeamPoints;
                break;
        }
        throw new \InvalidArgumentException('PoolTeam::__get ' . $name);
    }

    /**
     * @param  $data array
     * @return PoolTeam
     */
    static function createFromArray($data)
    {
        $team = new self();
        
        foreach($team->keys as $key => $type) {
            if (isset($data[$key])) {
                $team->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $team->$key = $data[$key];
            }
        }
        return $team;
    }
}