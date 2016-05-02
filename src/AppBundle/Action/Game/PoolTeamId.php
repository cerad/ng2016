<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $id
 * @property-read string $projectKey
 * @property-read string $poolTeamKey
 */
class PoolTeamId
{
    public $id;
    public $projectKey;
    public $poolTeamKey;
    
    public function __construct($projectKey,$poolTeamKey)
    {
        $this->id = $projectKey . ':' . $poolTeamKey;
        $this->projectKey  = $projectKey;
        $this->poolTeamKey = $poolTeamKey;
    }
    public function __toString()
    {
        return $this->id;
    }
}