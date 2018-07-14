<?php
namespace AppBundle\Action\Game;

/**
 * @property-read GameTeam homeTeam
 * @property-read GameTeam awayTeam
 * 
 * @property-read GameOfficial referee
 * @property-read GameOfficial ar1
 * @property-read GameOfficial ar2
 * 
 * @property-read string dow
 * @property-read string time
 * @property-read string poolView
 */
class Game
{
    public $gameId;
    public $projectId;
    public $gameNumber;
    public $role;
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Pending';
    public $status = 'Normal';
    public $reportText;
    public $reportState = 'Initial';
    public $selfAssign = 0;

    /** @var GameTeam[] */
    private $teams = [];
    
    /** @var GameOfficial[] */
    private $officials = [];

    private $keys = [
        'gameId'      => 'GameId',
        'projectId'   => 'ProjectId',
        'gameNumber'  => 'integer',
        'role'        => 'GameRole',
        'fieldName'   => 'ProjectFieldName',
        'venueName'   => 'ProjectVenueName',
        'start'       => 'datetime',
        'finish'      => 'datetime',
        'state'       => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'      => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
        'reportText'  => 'string',
        'reportState' => 'ReportState',
        'selfAssign'  => 'SelfAssign',
    ];

    public function getOfficial($slot)
    {
        return isset($this->officials[$slot]) ? $this->officials[$slot] : null;
    }
    public function getOfficials()
    {
        return $this->officials;
    }
    public function getTeams()
    {
        return $this->teams;
    }
    public function __get($name)
    {
        switch($name) {

            case 'homeTeam': return $this->teams[1];
            case 'awayTeam': return $this->teams[2];

            case 'referee':   return $this->officials[1];
            case 'ar1':       return $this->officials[2];
            case 'ar2':       return $this->officials[3];

            case 'dow':
                $start = \DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('D') : '???';
            
            case 'time':
                $start = \DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('g:i A') : '???';
            
            case 'poolView':
                
                $homePoolView = $this->teams[1]->poolView;
                $awayPoolView = $this->teams[2]->poolView;
                if ($homePoolView === $awayPoolView) {
                    return $homePoolView;
                }
                return sprintf('%s<hr class="separator">%s',$homePoolView,$awayPoolView);
        }
        throw new \InvalidArgumentException('Game::__get ' . $name);
    }
    
    /** 
     * @param  array $data
     * @return Game
     */
    static public function createFromArray($data)
    {
        $game = new self();

        foreach($game->keys as $key => $type) {
            if (isset($data[$key])) {
                $game->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $game->$key = $data[$key];
            }
        }
        foreach($data['teams'] as $teamData) {
            $game->teams[$teamData['slot']] = GameTeam::createFromArray($teamData);
        }
        foreach($data['officials'] as $officialData) {
            $game->officials[$officialData['slot']] = GameOfficial::createFromArray($officialData);
        }
        return $game;
    }
}