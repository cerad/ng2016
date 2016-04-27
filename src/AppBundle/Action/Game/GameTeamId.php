<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $id
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 */
class GameTeamId
{
    public $id;
    public $projectKey;
    public $gameNumber;
    public $slot;
    
    public function __construct($projectKey,$gameNumber,$slot)
    {
        $this->id = $projectKey . ':' . $gameNumber . ':' . $slot;
        $this->projectKey = $projectKey;
        $this->gameNumber = $gameNumber;
        $this->slot       = $slot;
    }
    public function __toString()
    {
        return $this->id;
    }
}