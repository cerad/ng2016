<?php
namespace Tests\AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\Project\ProjectFactory;
use AppBundle\Action\GameReport\GameReportRepository;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

class GameReportRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    protected $conn;

    /** @var  GameReportRepository */
    protected $gameReportRepository;

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../app/config/parameters.yml'));
        $params = $params['parameters'];

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname'   => $params['database_name_project'],
            'user'     => $params['database_user'],
            'password' => $params['database_password'],
            'host'     => $params['database_host'],
            'port'     => $params['database_port'],
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $this->gameReportRepository = new GameReportRepository($conn, new ProjectFactory());
    }
    public function test1()
    {
        $gameReport = $this->gameReportRepository->find('AYSONationalGames2014',11401);

        $this->assertEquals(11401,$gameReport['game']['number']);

        //print_r($gameReport);
    }
    public function testNotFound()
    {
        $gameReport = $this->gameReportRepository->find('AYSONationalGames2014x',11401);

        $this->assertNull($gameReport);
    }
}