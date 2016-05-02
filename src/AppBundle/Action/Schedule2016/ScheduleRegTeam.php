<?php
namespace AppBundle\Action\Schedule2016;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 */
class ScheduleRegTeam
{
    public $id;
    public $projectKey;
    public $teamNumber;
    public $teamKey;
    public $orgKey;

    public $name;
    public $points;
    public $coach;
    public $status;
    
    public $program;
    public $gender;
    public $age;
    public $division;
 
    private $keys = [
        'id'         => 'ProjectTeamId',
        'projectKey' => 'ProjectId',
        'teamNumber' => 'integer',
        'teamKey'    => 'ProjectTeamKey',
        'orgKey'     => 'PhysicalOrgId',

        'name'   => 'string',
        'points' => 'integer|null',
        'coach'  => 'string',
        'status' => 'string',
        
        'program'  => 'string',
        'gender'   => 'string',
        'agw'      => 'string',
        'division' => 'string',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('RegTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return ScheduleRegTeam
     */
    static public function createFromArray($data)
    {
        //$team = new ScheduleRegTeam();
        $team = new static();

        foreach(array_keys($team->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $team->$key = $data[$key];
            }
        }
        return $team;
    }
}