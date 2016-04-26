<?php
namespace Tests\AppBundle\Action\Game;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameTeam;

class GameTest extends \PHPUnit_Framework_TestCase
{
    public function testNewGame()
    {
        $game = new Game();
        $game->projectKey = 'WorldCup';
        $game->gameNumber = 999;

        $this->assertEquals('WorldCup',$game->projectKey);
        $this->assertEquals(999,       $game->gameNumber);
        
        $gameArray = $game->toArray();
        $this->assertEquals('WorldCup',$gameArray['projectKey']);
        
        $gameArray['status'] = 'Played';
        $game = $game->fromArray($gameArray);
        $this->assertEquals('Played',$game->status);
        
        $this->assertEquals('WorldCup:999',$game->GameId);
    }
    public function testNewGameTeams()
    {
        $projectKey = 'WorldCup2016';
        $gameNumber = 999;
        
        $game = new Game();
        $game->projectKey = $projectKey;
        $game->gameNumber = $gameNumber;

        $homeTeam = new GameTeam();
        $homeTeam->slot  = 1;
        $homeTeam->name  = 'Home Team';
        $homeTeam->score = 3;

        $awayTeam = new GameTeam();
        $awayTeam->slot  = 2;
        $awayTeam->name  = 'Visitors';
        $awayTeam->score = 1;

        $game->addTeam($homeTeam);
        $game->addTeam($awayTeam);

        $gameArray = $game->toArray();

        $this->assertEquals(3,         $gameArray['teams'][1]['score']);
        $this->assertEquals('Visitors',$gameArray['teams'][2]['name']);
    }
}