<?php
namespace AppBundle\Action\Schedule2016;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 */
class SchedulePoolTeam
{
    public $id;
    public $projectKey;

    public $poolKey;
    public $poolType;
    public $poolTeamKey;

    public $poolView;
    public $poolTypeView;
    public $poolTeamView;
    public $poolTeamSlotView;

    public $sourcePoolKeys;
    public $sourcePoolSlot;
    
    // From RegTeam
    public $regTeamId;
    public $teamNumber;
    public $teamKey;
    public $orgKey;

    public $name;
    public $coach;
    public $points;
    
    // Common
    public $program;
    public $gender;
    public $age;
    public $division;
 
    private $keys = [
        'id'         => 'ProjectTeamId',
        'projectKey' => 'ProjectId',
        
        'poolKey'     => 'PoolKey',
        'poolType'    => 'PoolType',
        'poolTeamKey' => 'PoolTeamKey',

        'poolView'         => 'string',
        'poolTypeView'     => 'string',
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',

        'sourcePoolKeys' => 'PoolKey[]',
        'sourcePoolSlot' => 'integer',

        'regTeamId'  => 'RegTeamId',
        'teamNumber' => 'integer',
        'teamKey'    => 'ProjectTeamKey',
        'orgKey'     => 'PhysicalOrgId',

        'name'   => 'string',
        'coach'  => 'string',
        'points' => 'integer|null',
        
        'program'  => 'string',
        'gender'   => 'string',
        'agw'      => 'string',
        'division' => 'string',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('PoolTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return SchedulePoolTeam
     */
    static public function createFromArray($data)
    {
        $team = new static();

        foreach(array_keys($team->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $team->$key = $data[$key];
            }
        }
        return $team;
    }
}