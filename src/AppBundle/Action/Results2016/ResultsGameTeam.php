<?php
namespace AppBundle\Action\Results2016;

class ResultsGameTeam
{
    public $slot;
    
    public $poolKey;
    public $poolTypeKey;
    public $poolTeamKey;
    public $poolTeamId;

    public $poolTeamView;
    public $poolTeamSlotView;

    public $regTeamName;

    public $results;

    public $pointsScored;
    public $pointsAllowed;
    public $pointsEarned;
    public $sportsmanship;

    public $playerWarnings;
    public $playerEjections;
    public $totalEjections;
    
    private $keys = [
        'slot' => 'integer',
        
        'poolKey'     => 'PoolKey',
        'poolTypeKey' => 'PoolTeamKey',
        'poolTeamKey' => 'PoolTeamKey',
        'poolTeamId'  => 'PoolTeamId',

        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',

        'regTeamName'   => 'string',

        'results'       => 'integer',
        'pointsScored'  => 'integer',
        'pointsAllowed' => 'integer',
        'pointsEarned'  => 'integer',
        'sportsmanship' => 'integer',
        
    ];
    /**
     * @param  array $data
     * @return ResultsGameTeam
     */
    static public function createFromArray($data)
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
        $misconduct = isset($data['misconduct']) ? unserialize($data['misconduct']) : [];
        $team->playerWarnings  = isset($misconduct['playerWarnings'])  ? (integer)$misconduct['playerWarnings']  : null;
        $team->playerEjections = isset($misconduct['playerEjections']) ? (integer)$misconduct['playerEjections'] : null;
        
        $team->totalEjections = $team->playerEjections;
        foreach(['coachEjections','benchEjections','specEjections'] as $key) {
            if (isset($misconduct[$key])) {
                $team->totalEjections += (integer)$misconduct[$key];
            }
        }
        return $team;
    }

}