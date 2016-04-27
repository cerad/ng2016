<?php
namespace AppBundle\Common;

use Doctrine\DBAL\Connection;

trait DatabaseInitTrait
{
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