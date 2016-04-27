<?php
namespace AppBundle\Action\Game;

class GameTest extends \PHPUnit_Framework_TestCase
{
    public function testNewGame()
    {
        $projectKey = 'WorldCup2016';
        $gameNumber = 999;

        $gameId = new GameId($projectKey,$gameNumber);
        $this->assertEquals('WorldCup2016:999',$gameId->id);
        $this->assertEquals('WorldCup2016:999',$gameId);

        $game = new Game($projectKey,$gameNumber);
        $this->assertEquals('WorldCup2016:999',$game->id);
        $this->assertEquals($projectKey,$game->projectKey);
        $this->assertEquals($gameNumber,$game->gameNumber);
        $this->assertEquals('Published',$game->state);

        $gameArray = $game->toArray();
        $this->assertEquals($projectKey,$gameArray['projectKey']);
        $this->assertEquals('Normal',   $gameArray['status']);

        $gameArray['status'] = 'Played';

        $game = Game::fromArray($gameArray);
        $this->assertEquals($gameNumber,$game->gameNumber);
        $this->assertEquals('Played',$game->status);
    }
    public function testNewPoolTeam()
    {
        $projectKey  = 'WorldCup2016';
        $poolTeamKey = 'U10B Core PP A1';
        $poolTeam = new PoolTeam($projectKey,$poolTeamKey);

        $this->assertEquals($poolTeamKey,$poolTeam->poolTeamKey);
    }
    public function testNewGameTeams()
    {
        $projectKey = 'WorldCup2016';
        $gameNumber = 999;
        
        $game = new Game($projectKey,$gameNumber);

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