<?php
namespace AppBundle\Action\Results2016;
/**
 * @property-read double winPercent
 * @property-read string winPercentView
 * 
 * @property-read double sportsmanshipPercent
 * @property-read double pointsScoredPercent
 * @property-read double pointsAgainstPercent
 */
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
        'regTeamPoints' => 'integer',
    ];
    public function mergeGameTeam(ResultsGameTeam $gameTeam)
    {
        $this->gamesTotal++;
        if ($gameTeam->results === null) {
            return;
        }
        $this->gamesPlayed++;
        if ($gameTeam->results === 1) {
            $this->gamesWon++;
        }
        $this->pointsEarned  += $gameTeam->pointsEarned;
        $this->pointsScored  += $gameTeam->pointsScored;
        $this->pointsAllowed += $gameTeam->pointsAllowed;
        $this->sportsmanship += $gameTeam->sportsmanship;

        $this->playerWarnings  += $gameTeam->playerWarnings;
        $this->playerEjections += $gameTeam->playerEjections;
        $this->totalEjections  += $gameTeam->totalEjections;
    }
    public function __get($name)
    {
        switch($name) {
            case 'winPercent':
            case 'winPercentView':
                if ($this->gamesPlayed === null) {
                    return null;
                }
                // pointsEarned already has soccerfest points
                $winPercent = ($this->pointsEarned * 1.0) / (($this->gamesPlayed * 10.0) + 6.0);
            //  $winPercent = (($this->pointsEarned + $this->regTeamPoints) * 1.0) / (($this->gamesPlayed * 10.0) + 6.0);
                if ($name === 'winPercent') {
                    return $winPercent;
                }
                return sprintf("%.02f",$winPercent * 100.0);

            case 'sportsmanshipPercent':
                if ($this->gamesPlayed === null) {
                    return null;
                }
                return ($this->sportsmanship * 1.0) / ($this->gamesPlayed * 40.0);

            case 'pointsScoredPercent':
            case 'pointsAgainstPercent':
                if ($this->gamesPlayed === null) {
                    return null;
                }
                return ($this->pointsScored * 1.0) / ($this->gamesPlayed * 1.0);
            
        }
        throw new \InvalidArgumentException('ResultsPoolTeam::__get ' . $name);
    }

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
        // Apply soccerfest points right from the start
        $poolTeam->pointsEarned = $poolTeam->regTeamPoints;

        return $poolTeam;
    }
}