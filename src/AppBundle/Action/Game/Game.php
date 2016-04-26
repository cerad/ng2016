<?php
namespace AppBundle\Action\Game;

/** 
 * @property-read string $GameId
 */
class Game
{
    public $projectKey;
    public $gameNumber;
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Published';
    public $status = 'Normal';

    private $teams = [];

    private $keys = [
        'projectKey' => 'ProjectKey',
        'gameNumber' => 'ProjectGameNumber',
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
    ];
    public function addTeam(GameTeam $team)
    {
        $this->teams[$team->slot] = $team;

        // How much do we really need here?  How much could the repo add
        $team->game       = $this;
        $team->projectKey = $this->projectKey;
        $team->gameNumber = $this->gameNumber;
    }
    public function hasTeam($slot) { return isset($this->teams[$slot]) ? true : false; }
    public function getTeam($slot) { return $this->teams[$slot]; }
    public function getHomeTeam()  { return $this->teams[1]; }
    public function getAwayTeam()  { return $this->teams[2]; }
    public function getTeams()     { return $this->teams;    }
    public function getTeamSlots() { return array_keys($this->teams); }
    
    public function __get($name)
    {
        switch($name) {
            case 'GameId':
                return $this->projectKey . ':' . $this->gameNumber;
        }
        throw new \InvalidArgumentException('Game::__get ' . $name);
    }
    // Arrayable Interface
    public function toArray()
    {
        $data = [];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
        }
        $data['teams'] = [];
        foreach($this->teams as $slot => $team) {
            $data['teams'][$slot] = $team->toArray();
        }
        return $data;
    }
    /** 
     * @param array $data
     * @return Game
     */
    public function fromArray($data)
    {
        foreach(array_keys($this->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $this->$key = $data[$key];
            }
        }
        return $this;
    }
}