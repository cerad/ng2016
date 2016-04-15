<?php
namespace Tests\AppBundle;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

abstract class AbstractTestDatabase extends PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    protected $conn;
    protected $params;

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../app/config/parameters.yml'));
        $this->params = $params = $params['parameters'];

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname'   => $params['database_name_test'],
            'user'     => $params['database_user'],
            'password' => $params['database_password'],
            'host'     => $params['database_host'],
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }
    protected function dropDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    protected function createDatabase(Connection $conn)
    {
        $cmd = sprintf("mysql -u%s -p%s %s < schema2016.sql",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase()
        );
        exec($cmd);
    }
    protected function resetDatabase(Connection $conn)
    {
        $this->dropDatabase  ($conn);
        $this->createDatabase($conn);
    }
}