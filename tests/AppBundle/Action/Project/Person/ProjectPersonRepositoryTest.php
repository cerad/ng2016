<?php
namespace Tests\AppBundle\Action\Project\Person;

use AppBundle\Action\Project\ProjectFactory;

use AppBundle\Action\Project\Person\ProjectPersonRepository;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

class ProjectPersonRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    protected $conn;

    /** @var  ProjectPersonRepository */
    protected $projectPersonRepository;

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../../app/config/parameters.yml'));
        $params = $params['parameters'];

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname'   => $params['database_name_users'],
            'user'     => $params['database_user'],
            'password' => $params['database_password'],
            'host'     => $params['database_host'],
            'port'     => $params['database_port'],
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $this->projectPersonRepository = new ProjectPersonRepository($conn, new ProjectFactory());
    }
    public function testFindOfficials()
    {
        $officials = $this->projectPersonRepository->findOfficials('AYSONationalGames2014');

        // 1314 registered, 1049 would referee, 762 actually refereed!
        $this->assertCount(1049,$officials);
        
        $official = $officials[9];

        //var_dump($official);

        $this->assertEquals('Adrian Backer',$official['name']);
        $this->assertEquals('2F1297A9-920E-44F1-8A75-3B52DFADA8F2',$official['personKey']);
        $this->assertEquals('AYSOR0122',$official['orgKey']);
        $this->assertEquals('Advanced', $official['badge']);

    }
}