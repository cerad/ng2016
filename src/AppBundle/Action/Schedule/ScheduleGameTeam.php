<?php
namespace AppBundle\Action\Schedule;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 */
class ScheduleGameTeam
{
    public $gameTeamId;
    public $gameId;
    public $gameNumber;
    public $slot;

    public $regTeamId;
    public $regTeamName;
    public $division;

    public $poolTeamId;
    public $poolTeamKey;

    public $poolView;
    public $poolTypeView;
    public $poolTeamView;
    public $poolTeamSlotView;

    private $keys = [

        'gameTeamId' => 'GameTeamId',
        'gameId'     => 'GameId',
        'gameNumber' => 'integer',
        'slot'       => 'integer',

        'regTeamId'   => 'RegTeamId',
        'regTeamName' => 'string',
        'division'    => 'string',
        
        'poolView'         => 'string',
        'poolTypeView'     => 'string',
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',

        'poolTeamId'  => 'PoolTeamId',
        'poolTeamKey' => 'PoolTeamKey',
    ];

    public function __get($name)
    {
        switch($name) {

        }
        throw new \InvalidArgumentException('GameTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return ScheduleGameTeam
     */
    static public function createFromArray($data)
    {
        $gameTeam = new static();

        foreach(array_keys($gameTeam->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $gameTeam->$key = $data[$key];
            }
        }
        return $gameTeam;
    }
}
