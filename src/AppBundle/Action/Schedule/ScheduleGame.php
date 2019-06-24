<?php
namespace AppBundle\Action\Schedule;

use DateTime;

use InvalidArgumentException;

/**
 * @property-read ScheduleGameTeam homeTeam
 * @property-read ScheduleGameTeam awayTeam
 * 
 * @property-read ScheduleGameOfficial referee
 * @property-read ScheduleGameOfficial ar1
 * @property-read ScheduleGameOfficial ar2
 * 
 * @property-read string dow
 * @property-read string time
 * @property-read string poolView
 */
class ScheduleGame
{
    public $gameId;
    public $projectId;
    public $gameNumber;
    
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Pending';
    public $status = 'Normal';
    public $selfAssign;

    /** @var ScheduleGameTeam[] */
    private $teams = [];
    
    /** @var ScheduleGameOfficial[] */
    private $officials = [];

    private $keys = [
        'gameId'     => 'GameId',
        'projectId'  => 'ProjectId',
        'gameNumber' => 'integer',
        
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
        'selfAssign' => 'integer',
    ];

    /**
     * @return ScheduleGameOfficial[]
     */
    public function getOfficials()
    {
        return $this->officials;
    }
    public function __get($name)
    {
        switch($name) {

            case 'homeTeam': return $this->teams[1];
            case 'awayTeam': return $this->teams[2];

            case 'referee':   return $this->officials[1];
            case 'ar1':       return $this->officials[2];
            case 'ar2':       return $this->officials[3];

            case 'date':
                $date = explode(' ', $this->start);
                return $date[0];

            case 'dow':
                $start = DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('D') : '???';
            
            case 'time':
                $start = DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('g:i A') : '???';
            
            case 'poolView':
                
                $homePoolView = $this->teams[1]->poolView;
                $awayPoolView = $this->teams[2]->poolView;
                if ($homePoolView === $awayPoolView) {
                    return $homePoolView;
                }
                return sprintf('%s<hr class="separator">%s',$homePoolView,$awayPoolView);

            case 'selfAssign':
                return (bool)$this->selfAssign;
        }
        throw new InvalidArgumentException('ScheduleGame::__get ' . $name);
    }
    
    /** 
     * @param  array $data
     * @return ScheduleGame
     */
    static public function createFromArray($data)
    {
        $game = new static();
        
        foreach(array_keys($game->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $game->$key = $data[$key];
            }
        }
        foreach($data['teams'] as $teamData) {
            $game->teams[$teamData['slot']] = ScheduleGameTeam::createFromArray($teamData);
        }
        foreach($data['officials'] as $officialData) {
            $game->officials[$officialData['slot']] = ScheduleGameOfficial::createFromArray($officialData);
        }
        return $game;
    }
}