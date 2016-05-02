<?php
namespace AppBundle\Action\Game;

use AppBundle\Common\DatabaseTrait;
use AppBundle\Common\DirectoryTrait;
//  Symfony\Component\Yaml\Yaml;

//  Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class GameRepositoryTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    use DirectoryTrait;
    
    private $gameDatabaseKey = 'database_name_test';
    private $poolDatabaseKey = 'database_name_test';

    private $gameConn;
    private $poolConn;

    private function createPoolTeamRepository()
    {
        if (!$this->poolConn) {
             $this->poolConn = $this->getConnection($this->poolDatabaseKey);
        }
        return new PoolTeamRepository($this->poolConn);
    }
    private function createGameRepository()
    {
        if (!$this->gameConn) {
             $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        return new GameRepository($this->gameConn,$this->createPoolTeamRepository());
    }
    public function testSave()
    {
        $this->gameConn = $conn = $this->getConnection($this->gameDatabaseKey);
        
        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';
        
        $this->resetDatabase($conn,$schemaFile);

        $repo = $this->createGameRepository();

        $projectKey = 'WorldCup2016';
        $gameNumber = 999;

        $game = new Game($projectKey,$gameNumber);

        $game = $repo->save($game);
        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);

        $game->state = 'Played';
        $game = $repo->save($game);
        $this->assertEquals($gameNumber,$game->gameNumber);
        $this->assertEquals('Played',   $game->state);
    }
    public function testSaveTeam()
    {
        $projectKey = 'WorldCup2016';
        $gameNumber = 888;

        // Create Pools
        $poolTeamRepository = $this->createPoolTeamRepository();

        $poolTeam1 = new PoolTeam($projectKey,'U10B PP A1','U10B PP','PP');
        $poolTeam2 = new PoolTeam($projectKey,'U10B PP A2','U10B PP','PP');

        $poolTeamRepository->save($poolTeam1);
        $poolTeamRepository->save($poolTeam2);

        // Create Games
        $gameRepository = $this->createGameRepository();

        $game = new Game($projectKey,$gameNumber);

        $game->fieldName = 'John Hunt 3';

        $homeTeam = new GameTeam($projectKey,$gameNumber,1);
        $homeTeam->name = 'Home Team';
        $homeTeam->pointsScored = 3;
        $homeTeam->setPoolTeam($poolTeam1);

        $awayTeam = new GameTeam($projectKey,$gameNumber,2);
        $awayTeam->name = 'Visitors';
        $awayTeam->pointsScored = 1;
        $awayTeam->setPoolTeam($poolTeam2);

        $game->addTeam($homeTeam);
        $game->addTeam($awayTeam);

        $gameRepository->save($game);

        // Another game
        $gameNumber = 777;
        $game = new Game($projectKey,$gameNumber);

        $game->fieldName = 'John Hunt 4';

        $homeTeam = new GameTeam($projectKey,$gameNumber,1);
        $homeTeam->name  = 'Home Team';
        $homeTeam->setPoolTeam($poolTeam1);

        $awayTeam = new GameTeam($projectKey,$gameNumber,2);
        $awayTeam->name  = 'Visitors';
        $awayTeam->setPoolTeam($poolTeam2);

        $game->addTeam($homeTeam);
        $game->addTeam($awayTeam);

        $gameRepository->save($game);
    }
    public function testFind()
    {
        $repo = $this->createGameRepository();

        $projectKey = 'WorldCup2016';
        $gameNumber = 999;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);

        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);

    }
    public function testFindTeam()
    {
        $repo = $this->createGameRepository();

        $projectKey = 'WorldCup2016';
        $gameNumber = 888;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);

        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);

        $this->assertEquals('Home Team',  $game->homeTeam->name);
        $this->assertEquals('John Hunt 3',$game->awayTeam->game->fieldName);
        $this->assertEquals('U10B PP A2', $game->awayTeam->poolTeam->poolTeamKey);

        $this->assertInternalType('integer',$game->awayTeam->pointsScored);

    }
    public function testUpdate()
    {
        $repo = $this->createGameRepository();

        $projectKey = 'WorldCup2016';
        $gameNumber = 777;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);
        $this->assertEquals(777,$game->gameNumber);
        $this->assertInternalType('null',$game->awayTeam->pointsScored);

        $game->homeTeam->pointsScored = 5;
        $game->awayTeam->pointsScored = 6;

        $repo->save($game);

        $game = $repo->find($gameId);
        $this->assertEquals(777,$game->gameNumber);
        $this->assertInternalType('integer',$game->awayTeam->pointsScored);
        $this->assertEquals(5,$game->homeTeam->pointsScored);
    }
}