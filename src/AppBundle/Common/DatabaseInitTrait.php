<?php
namespace AppBundle\Common;

use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

trait DatabaseInitTrait
{
    protected $conns = [];

    /**
     * @param  string $databaseNameKey
     * @return Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getConnection($databaseNameKey)
    {
        if (isset( $this->conns[$databaseNameKey])) {
            return $this->conns[$databaseNameKey];
        }
        $dir = __DIR__;
        foreach(['src','tests'.'vendor'] as $key) {
            $pos = strpos($dir, DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR);
            if ($pos !== false) {
                $dir = substr($dir, 0, $pos);
            }
        }
        $dir .= DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        
        $params = Yaml::parse(file_get_contents($dir . 'parameters.yml'));
        $this->params = $params = $params['parameters'];

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'dbname'   => $params[$databaseNameKey],
            'user'     => $params['database_user'],
            'password' => $params['database_password'],
            'host'     => $params['database_host'],
            'port'     => $params['database_port'],
            'driver'   => $params['database_driver'],
        );
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->conns[$databaseNameKey] = $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        
        return $conn;
    }

    protected function dropDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    protected function createDatabase(Connection $conn,$schemaFile)
    {
        $cmd = sprintf("mysql -u%s -p%s %s < %s",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase(),
            $schemaFile
        );
        exec($cmd);
    }
    protected function resetDatabase(Connection $conn, $schemaFile)
    {
        $this->dropDatabase  ($conn);
        $this->createDatabase($conn,$schemaFile);
    }

}