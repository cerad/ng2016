<?php
namespace AppBundle\Action\Game;

/* ==============================================
 * Experimental class, not currently being used
 * Trying to find a way to hide most of the implementation details from the app
 *
 * One goal is to have domain specific game entity implementations sharing the same GameId class
 */
class GameId
{
    private $projectId;
    private $gameNumber;
    
    public function __construct($projectId,$gameNumber)
    {
        $this->projectId  = $projectId;
        $this->gameNumber = $gameNumber;
    }
    public function toArray()
    {
        return [
            'projectId'  => $this->projectId,
            'gameNumber' => $this->gameNumber,
        ];
    }
    public function toString()
    {
        return $this->projectId . ':' . $this->gameNumber;
    }
    // Maybe static create functions?
}