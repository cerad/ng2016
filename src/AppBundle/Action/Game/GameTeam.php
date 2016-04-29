<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 * @property-read PoolTeam $poolTeam
 */
class GameTeam
{
    /** @var  GameTeamId */
    public $id;

    public $name; // Sync with ProjectTeam?

    public $results;
    public $resultsDetail;

    public $pointsScored;
    public $pointsAllowed;
    public $pointsEarned;
    public $pointsDeducted;
    public $sportsmanship;
    public $injuries;

    public $misconduct;

    public $orgKey; // Useful for seasonal schedule

    /** @var  PoolTeam */
    private $poolTeamPrivate;
    
    /** @var  ProjectTeam */
    public $projectTeam;

    /** @var  Game */
    public $game; // Reference, do we need it? Or GameId?
    
    private $keys = [

        'name' => 'string',

        'results'        => 'integer|null',
        'resultsDetail'  => 'string|null',

        'pointsScored'   => 'integer|null',
        'pointsAllowed'  => 'integer|null',
        'pointsEarned'   => 'integer|null',
        'pointsDeducted' => 'integer|null',
        'sportsmanship'  => 'integer|null',
        'injuries'       => 'integer|null',
        'misconduct'     => 'array',

        'orgKey' => 'PhysicalOrgId', // Could be part of project team
    ];
    public function __construct($projectKey,$gameNumber,$slot)
    {
        $this->id = new GameTeamId($projectKey,$gameNumber,$slot);
    }
    public function setPoolTeam(PoolTeam $poolTeam)
    {
        $this->poolTeamPrivate = $poolTeam;
    }
    public function __get($name)
    {
        switch($name) {
            case 'projectKey': return $this->id->projectKey;
            case 'gameNumber': return $this->id->gameNumber;
            case 'slot':       return $this->id->slot;

            case 'poolTeam':
                return $this->poolTeamPrivate ? : new PoolTeam(null,null);
        }
        throw new \InvalidArgumentException('GameTeam::__get ' . $name);
    }

    // Arrayable Interface
    public function toArray()
    {
        $data = [
            'id'         => $this->id->id,
            'projectKey' => $this->id->projectKey,
            'gameNumber' => $this->id->gameNumber,
            'slot'       => $this->id->slot,
        ];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
        }
        // Not sure I need the complete pool team data here
        $data['poolTeam'] = isset($this->poolTeamPrivate) ? $this->poolTeamPrivate->toArray() : null;
        
        $data['poolTeamId'] = isset($this->poolTeamPrivate) ? $this->poolTeamPrivate->id->id : null;
        
        // Want gameId??? or any game data?
        $data['gameId'] = $this->game->id->id;
        
        return $data;
    }
    /**
     * @param array $data
     * @return GameTeam
     */
    static public function createFromArray($data)
    {
        $gameTeam = new GameTeam($data['projectKey'],$data['gameNumber'],$data['slot']);

        foreach(array_keys($gameTeam->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $gameTeam->$key = $data[$key];
            }
        }
        if ($data['poolTeam']) {
            $gameTeam->poolTeamPrivate = PoolTeam::createFromArray($data['poolTeam']);
        }
        return $gameTeam;
    }
}