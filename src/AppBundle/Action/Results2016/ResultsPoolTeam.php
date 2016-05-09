<?php
namespace AppBundle\Action\Results2016;

class ResultsPoolTeam
{
    public $poolKey;
    public $poolTypeKey;
    public $poolTeamKey;
    public $poolTeamId;
    
    public $poolTeamView;
    public $poolTeamSlotView;
    
    public $regTeamName;
    public $regTeamPoints;
    
    public $pointsEarned;
    public $winPercent;
    public $standing;
    
    public $gamesTotal;
    public $gamesPlayed;
    public $gamesWon;
    
    public $pointsScored;
    public $pointsAllowed;
    public $sportsmanship;
    
    public $playerWarnings;
    public $playerEjections;
    public $totalEjections;
    
    private $keys = [
        'poolKey'     => 'PoolKey',
        'poolTypeKey' => 'PoolTeamKey',
        'poolTeamKey' => 'PoolTeamKey',
        'poolTeamId'  => 'PoolTeamId',
        
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',
    
        'regTeamName'   => 'string',
        'regTeamPoints' => 'string',
    ];
    /**
     * @param  $data array
     * @return ResultsPoolTeam
     */
    static function createFromArray($data)
    {
        $poolTeam = new self();

        foreach(array_keys($poolTeam->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $poolTeam->$key = $data[$key];
            }
        }
        return $poolTeam;
    }
}