<?php
namespace AppBundle\Action\Game;

use AppBundle\Common\DatabaseInitTrait;
use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class GameRepositoryTest extends PHPUnit_Framework_TestCase
{
    use DatabaseInitTrait;

    /** @var  Connection */
    protected $conn;
    protected $params;
    protected $schemaFile      = './src/AppBundle/Action/Game/schema.sql';
    protected $databaseNameKey = 'database_name_test';

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../app/config/parameters.yml'));
        $this->params = $params = $params['parameters'];

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname'   => $params[$this->databaseNameKey],
            'user'     => $params['database_user'],
            'password' => $params['database_password'],
            'host'     => $params['database_host'],
            'port'     => $params['database_port'],
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }
    public function testSave()
    {
        $this->resetDatabase($this->conn,$this->schemaFile);

        $repo = new GameRepository($this->conn, new PoolTeamRepository($this->conn));

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
        $poolTeamRepository = new PoolTeamRepository($this->conn);

        $poolTeam1 = new PoolTeam($projectKey,'U10B PP A1','U10B PP','PP');
        $poolTeam2 = new PoolTeam($projectKey,'U10B PP A2','U10B PP','PP');

        $poolTeamRepository->save($poolTeam1);
        $poolTeamRepository->save($poolTeam2);

        // Create Games
        $gameRepository = new GameRepository($this->conn,$poolTeamRepository);

        $game = new Game($projectKey,$gameNumber);

        $game->fieldName = 'John Hunt 3';

        $homeTeam = new GameTeam($projectKey,$gameNumber,1);
        $homeTeam->name  = 'Home Team';
        $homeTeam->goalsScored = 3;
        $homeTeam->setPoolTeam($poolTeam1);

        $awayTeam = new GameTeam($projectKey,$gameNumber,2);
        $awayTeam->name  = 'Visitors';
        $awayTeam->goalsScored = 1;
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
        $repo = new GameRepository($this->conn, new PoolTeamRepository($this->conn));

        $projectKey = 'WorldCup2016';
        $gameNumber = 999;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);

        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);

    }
    public function testFindTeam()
    {
        $repo = new GameRepository($this->conn, new PoolTeamRepository($this->conn));

        $projectKey = 'WorldCup2016';
        $gameNumber = 888;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);

        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);

        $this->assertEquals('Home Team',  $game->homeTeam->name);
        $this->assertEquals('John Hunt 3',$game->awayTeam->game->fieldName);
        $this->assertEquals('U10B PP A2', $game->awayTeam->poolTeam->poolTeamKey);

        $this->assertInternalType('integer',$game->awayTeam->goalsScored);

    }
    public function testUpdate()
    {
        $repo = new GameRepository($this->conn, new PoolTeamRepository($this->conn));

        $projectKey = 'WorldCup2016';
        $gameNumber = 777;

        $gameId = new GameId($projectKey,$gameNumber);

        $game = $repo->find($gameId);
        $this->assertEquals(777,$game->gameNumber);
        $this->assertInternalType('null',$game->awayTeam->goalsScored);

        $game->homeTeam->goalsScored = 5;
        $game->awayTeam->goalsScored = 6;

        $repo->save($game);

        $game = $repo->find($gameId);
        $this->assertEquals(777,$game->gameNumber);
        $this->assertInternalType('integer',$game->awayTeam->goalsScored);
        $this->assertEquals(5,$game->homeTeam->goalsScored);
    }
}