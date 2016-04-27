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

        $repo = new GameRepository($this->conn);

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
    public function testFind()
    {
        $repo = new GameRepository($this->conn);

        $projectKey = 'WorldCup2016';
        $gameNumber = 999;

        $gameId = new GameId($projectKey,$gameNumber);
        
        $game = $repo->find($gameId);
        
        $this->assertEquals($gameNumber,    $game->gameNumber);
        $this->assertInternalType('integer',$game->gameNumber);
        
    }
}