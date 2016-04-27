<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * 
 * @property-read GameTeam $homeTeam
 * @property-read GameTeam $awayTeam
 */
class Game
{
    /** @var GameId  */
    public $id;

    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Published';
    public $status = 'Normal';

    private $teams = []; // Just because some stuff need some code

    private $keys = [
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
    ];
    public function __construct($projectKey,$gameNumber)
    {
        $this->id = new GameId($projectKey,$gameNumber);
    }
    public function addTeam(GameTeam $team)
    {
        $this->teams[$team->slot] = $team;

        // How much do we really need here?  How much could the repo add
        $team->game = $this;
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
            case 'projectKey': return $this->id->projectKey;
            case 'gameNumber': return $this->id->gameNumber;

            case 'homeTeam':   return $this->teams[1];
            case 'awayTeam':   return $this->teams[2];
        }
        throw new \InvalidArgumentException('Game::__get ' . $name);
    }
    
    // Arrayable Interface
    public function toArray()
    {
        $data = [
            'id'         => $this->id->id,
            'projectKey' => $this->id->projectKey,
            'gameNumber' => $this->id->gameNumber,
        ];
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
    static public function fromArray($data)
    {
        $game = new Game($data['projectKey'],$data['gameNumber']);
        
        foreach(array_keys($game->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $game->$key = $data[$key];
            }
        }
        foreach($data['teams'] as $teamData) {
            $gameTeam = GameTeam::fromArray(($teamData));
            $game->addTeam($gameTeam);
        }
        return $game;
    }
}