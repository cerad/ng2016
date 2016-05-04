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
    public $regTeamId;
    public $teamName;
    public $division;
 
    private $keys = [
        'regTeamId' => 'RegTeamId',
        'teamName'  => 'string',
        'division'  => 'string',
    ];
    
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