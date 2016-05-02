<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $id
 * @property-read string $projectKey
 * @property-read string $gameNumber
 */
class GameId
{
    public $id;
    public $projectKey;
    public $gameNumber;
    
    public function __construct($projectKey,$gameNumber)
    {
        $this->id = $projectKey . ':' . $gameNumber;
        $this->projectKey = $projectKey;
        $this->gameNumber = $gameNumber;
    }
    public function __toString()
    {
        return $this->id;
    }
}