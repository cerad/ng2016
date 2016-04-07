<?php
namespace Tests\AppBundle\Action\Project\User;

use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\Project\User\ProjectUserProvider;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

class ProjectPersonRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    protected $userConn;

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
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->userConn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        //$this->projectPersonRepository = new ProjectPersonRepository($conn, new ProjectFactory());
    }
    public function testLoadUserByUsername()
    {
        $provider = new ProjectUserProvider($this->userConn);

        $username = 'ahundiak@gmail.com';

        $user = $provider->loadUserByUsername($username);

        $this->assertEquals($username,$user->getUsername());
    }
    public function testRefreshUser()
    {
        $provider = new ProjectUserProvider($this->userConn);
        $user = new ProjectUser();
        $user->id = 10;

        $user = $provider->refreshUser($user);

        $this->assertEquals('godder4@verizon.net',$user->getUsername());
    }
}