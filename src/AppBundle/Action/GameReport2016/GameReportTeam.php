<?php
namespace AppBundle\Action\GameReport2016;

/**
 * @property-read string $projectId
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 */
class GameReportTeam
{
    public $gameTeamId;
    public $gameId;
    public $gameNumber;
    public $slot;

    public $results;
    public $resultsDetail; // Maybe view?
    
    public $pointsScored;
    public $pointsAllowed;
    public $pointsEarned;
    public $pointsDeducted;
    public $sportsmanship;
    public $injuries;
    
    public $regTeamId;
    public $regTeamName;
    public $regTeamPoints;

    public $poolKey;
    public $poolTypeKey;
    public $poolTeamKey;
    
    /** @var  GameReportTeamMisconduct */
    public $misconduct;
    
    private $keys = [

        'gameTeamId' => 'GameTeamId',
        'gameId'     => 'GameId',
        'gameNumber' => 'integer',
        'slot'       => 'integer',

        'results'       => 'integer',
        'resultsDetail' => 'string',
        
        'pointsScored'   => 'integer',
        'pointsAllowed'  => 'integer',
        'pointsEarned'   => 'integer',
        'pointsDeducted' => 'integer',
        'sportsmanship'  => 'integer',
        'injuries'       => 'integer',
        
        'regTeamId'     => 'RegTeamId',
        'regTeamName'   => 'string',
        'regTeamPoints' => 'integer',

        'poolKey'     => 'string',
        'poolTypeKey' => 'string',
        'poolTeamKey' => 'string',
    ];

    public function __get($name)
    {
        switch($name) {
            case 'resultsView': return $this->resultsDetail;
        }
        throw new \InvalidArgumentException('GameReportTeam::__get ' . $name);
    }

    public function toUpdateArray()
    {
        return [
            'gameTeamId'     => $this->gameTeamId,
            'results'        => $this->results,
            'resultsDetail'  => $this->resultsDetail,
            'pointsScored'   => $this->pointsScored,
            'pointsAllowed'  => $this->pointsAllowed,
            'pointsEarned'   => $this->pointsEarned,
            'pointsDeducted' => $this->pointsDeducted,
            'sportsmanship'  => $this->sportsmanship,
            'injuries'       => $this->injuries,
            'misconduct'     => $this->misconduct->toUpdateArray(),
        ];
    }
    /**
     * @param  array $data
     * @return GameReportTeam
     */
    static public function createFromArray($data)
    {
        $gameReportTeam = new self();

        foreach($gameReportTeam->keys as $key => $type) {
            if (isset($data[$key])) {
                $gameReportTeam->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $gameReportTeam->$key = $data[$key]; // To allow setting null values
            }
        }
        $gameReportTeam->misconduct = GameReportTeamMisconduct::createFromArray($data['misconduct']);
        
        return $gameReportTeam;
    }
}