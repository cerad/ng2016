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
    protected $schemaFile      = 'schema2016.sql';
    protected $databaseNameKey = 'database_name_test';

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../app/config/parameters.yml'));
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
    protected function dropDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    protected function createDatabase(Connection $conn)
    {
        $cmd = sprintf("mysql -u%s -p%s %s < %s",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase(),
            $this->schemaFile
        );
        exec($cmd);
    }
    protected function resetDatabase(Connection $conn)
    {
        $this->dropDatabase  ($conn);
        $this->createDatabase($conn);
    }
}