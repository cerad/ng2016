<?php
namespace Tests\AppBundle\Action\Project\Game;

use AppBundle\Action\Project\Game\ProjectGame;

class ProjectGameTest extends \PHPUnit_Framework_TestCase
{
    public function testNew()
    {
        $game = new ProjectGame('WorldCup',999);

        $this->assertEquals('WorldCup',$game['projectKey']);
        $this->assertEquals(999,$game->number);
        
        $gameData = $game->toArray();
        $this->assertEquals('WorldCup',$gameData['projectKey']);
        
        $gameData['id'] = 1;
        $game = $game->fromArray($gameData);
        $this->assertEquals(1,$game->id);
    }
}