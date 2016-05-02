<?php
namespace AppBundle\Action\Schedule2016;

/**
 * @property-read ScheduleGameTeam homeTeam
 * @property-read ScheduleGameTeam awayTeam
 * @property-read string dow
 * @property-read string time
 * @property-read string poolView
 */
class ScheduleGame
{
    public $id;
    public $projectKey;
    public $gameNumber;
    
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Pending';
    public $status = 'Normal';

    /** @var ScheduleGameTeam[] */
    private $teams = [];
    
    private $keys = [
        'id'         => 'ProjectGameId',
        'projectKey' => 'ProjectId',
        'gameNumber' => 'integer',
        
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
    ];

    public function __get($name)
    {
        switch($name) {
            
            case 'homeTeam':   return $this->teams[1];
            case 'awayTeam':   return $this->teams[2];

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
        throw new \InvalidArgumentException('ScheduleGame::__get ' . $name);
    }
    
    /** 
     * @param array $data
     * @return ScheduleGame
     */
    static public function fromArray($data)
    {
        $game = new ScheduleGame();
        
        foreach(array_keys($game->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $game->$key = $data[$key];
            }
        }
        foreach($data['teams'] as $teamData) {
            $game->teams[$teamData['slot']] = ScheduleGameTeam::fromArray($teamData);
        }
        return $game;
    }
}