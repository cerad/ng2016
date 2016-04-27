<?php
namespace AppBundle\Action\Game;

class GameTeam
{
    public $projectKey;
    public $gameNumber;
    
    public $slot;
    public $name; // Sync with ProjectTeam?
    public $score;
    
    public $orgKey; // Useful for seasonal schedule

    /** @var  PoolTeam */
    public $poolTeam;
    
    /** @var  ProjectTeam */
    public $projectTeam;

    /** @var  Game */
    public $game; // Reference, do we need it? Or GameId?
    
    private $keys = [
        'projectKey' => 'ProjectKey',
        'gameNumber' => 'ProjectGameNumber',
        
        'slot'   => 'integer', // 1 = Home, 2 = Away
        'name'   => 'string',
        'score'  => 'integer|null',
        
        'orgKey' => 'PhysicalOrgKey', // Could be part of project team
    ];
    
    // Arrayable Interface
    public function toArray()
    {
        $data = [];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
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