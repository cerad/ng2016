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

        $game->fieldName = 'John Hunt 1';

        $homeTeam = new GameTeam($projectKey,$gameNumber,1);
        $homeTeam->name = 'Home Team';
        $homeTeam->goalsScored = 3;
        $homeTeam->setPoolTeam(new PoolTeam($projectKey,'U10B PP A1','U10B PP','PP'));
        
        $awayTeam = new GameTeam($projectKey,$gameNumber,2);
        $awayTeam->name = 'Visitors';
        $awayTeam->goalsScored = 1;
        $awayTeam->setPoolTeam(new PoolTeam($projectKey,'U10B PP A2','U10B PP','PP'));

        $game->addTeam($homeTeam);
        $game->addTeam($awayTeam);

        $gameArray = $game->toArray();

        $this->assertEquals(3,         $gameArray['teams'][1]['goalsScored']);
        $this->assertEquals('Visitors',$gameArray['teams'][2]['name']);

        $game = Game::fromArray($gameArray);
        $this->assertEquals('Home Team',  $game->getTeam(1)->name);
        $this->assertEquals('Home Team',  $game->homeTeam->name);
        $this->assertEquals('John Hunt 1',$game->awayTeam->game->fieldName);
        $this->assertEquals('U10B PP A2', $game->awayTeam->poolTeam->poolTeamKey);
    }
}