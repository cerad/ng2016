<?php
namespace Tests\AppBundle\Common\Schema;


use AppBundle\Action\Project\Person\ProjectPersonRepository;
use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\Project\User\ProjectUserProvider;
use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
//use Doctrine\DBAL\Platforms\MySqlPlatform;

use PHPUnit_Framework_TestCase;

class SchemaTest extends PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    private $conn;
    private $params;

    public function setUp()
    {
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../app/config/parameters.yml'));
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
    private function clearDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    private function createDatabase()
    {
        $params = $this->params;

        $cmd = sprintf("mysql -u%s -p%s %s < schema2016.sql",
            $params['database_user'],
            $params['database_password'],
            $params['database_name_test']
        );
        exec($cmd);
    }
    private function execQueries(Connection $conn,$queries)
    {
        foreach($queries as $query) {
            $conn->exec($query);
        }
    }

    public function sest1()
    {
        $schema = new Schema();

        $this->createTableUsers($schema);

        $conn = $this->conn;
        $this->clearDatabase($conn);

        $platform = $this->conn->getDatabasePlatform();
        $createQueries = $schema->toSql($platform);
        foreach($createQueries as $sql) {
            $conn->exec($sql);
        }

        //$dropQueries   = $schema->toDropSql($platform);
        //var_dump($dropQueries);
        //var_dump($createQueries);

    }
    private function createTableUsers(Schema $schema)
    {
        $users = $schema->createTable('users');

        $users->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);

        $users->setPrimaryKey(['id']);

        $users->addColumn('personKey','string',['length' => 40, 'notnull' => true]);

        $users->addColumn('verified','boolean',['notnull' => false, 'default' => false]);
    }
    public function sest2()
    {
        $conn = $this->conn;
        $this->clearDatabase($conn);

        $schema = new Schema();

        $schemaFile = Yaml::parse(file_get_contents(__DIR__ . '/schema.yml'));

        foreach($schemaFile['tables'] as  $tableName => $tableMeta) {
            $table = $schema->createTable($tableName);
            foreach($tableMeta['columns'] as $columnName => $columnMeta) {
                $params = [];
                foreach(['unsigned','autoincrement','length','notnull','default'] as $key) {
                    if (isset($columnMeta[$key])) $params[$key] = $columnMeta[$key];
                }
                $table->addColumn($columnName,$columnMeta['type'],$params);
                if (isset($columnMeta['primaryKey']) && $columnMeta['primaryKey']) {
                    // Naming a primary key is not supported
                    $table->setPrimaryKey([$columnName],$tableName . '_primaryKey');
                }
            }
        }
        $platform = $conn->getDatabasePlatform();
        $createQueries = $schema->toSql($platform);

        $this->execQueries($conn,$createQueries);

        //var_dump($createQueries);
    }
    public function test3()
    {
        $conn = $this->conn;

        $this->clearDatabase ($conn);
        $this->createDatabase();

        $personKey  = 'person-0001';
        $projectKey = 'AYSONationalGames2016';

        $sql = <<<EOD
INSERT INTO users (name,email,username,personKey,roles) VALUES(?,?,?,?,?);
EOD;
        $insertUserStmt = $conn->prepare($sql);
        $insertUserStmt->execute([
            'Art Hundiak',
            'ahundiak@fake.com',
            'ahundiak',
            $personKey,
            'ROLE_USER',
        ]);
        $userProvider = new ProjectUserProvider($projectKey, $conn, $conn, []);

        $user = $userProvider->loadUserByUsername('ahundiak');
        $this->assertEquals('ahundiak', $user['username']);
        $this->assertEquals('ahundiak@fake.com', $user['email']);
        $this->assertEquals('ROLE_USER', $user['roles'][0]);

        $userId = $user['id'];
        $user = new ProjectUser();
        $user['id'] = $userId;

        $user = $userProvider->refreshUser($user);
        $this->assertEquals('Art Hundiak', $user['name']);
        $this->assertEquals($projectKey, $user['projectKey']);
        $this->assertNull($user['registered']);

        $sql = <<<EOD
INSERT INTO projectPersons(projectKey,personKey,name,email,registered) VALUES(?,?,?,?,?);
EOD;
        $insertProjectPersonStmt = $conn->prepare($sql);
        $insertProjectPersonStmt->execute([
            $projectKey,
            $personKey,
            'Art Hundiak Registered',
            'ahundiak@fake.com',
            true,
        ]);
        $user = $userProvider->loadUserByUsername('ahundiak');

        $this->assertEquals(1, $user['registered']);
        $this->assertCount(1, $user['roles']);

        $projectPersonRepository = new ProjectPersonRepository($conn);
        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        $this->assertNotNull($projectPerson);

        $sql = <<<EOD
INSERT INTO projectPersonRoles(projectPersonId,role,active) VALUES(?,?,?);
EOD;
        $insertProjectPersonRoleStmt = $conn->prepare($sql);
        $insertProjectPersonRoleStmt->execute([
            $projectPerson['id'],
            'ROLE_REFEREE',
            true,
        ]);

        $user = $userProvider->loadUserByUsername('ahundiak');

        $this->assertCount(2, $user['roles']);

        $this->assertEquals('ROLE_REFEREE', $user['roles'][1]);
        
        // For insert unique names
        $name = $projectPersonRepository->generateUniqueName($projectKey,'Art Hundiak Registered');
        $this->assertEquals('Art Hundiak Registered(2)',$name);
    }
    public function sest3()
    {
        $conn = $this->conn;

        $this->clearDatabase ($conn);
        $this->createDatabase();
        
        $data = Yaml::parse(file_get_contents(__DIR__ . '/data.yml'));

        $sql = <<<EOD
INSERT INTO users (name,email,username,personKey,roles) VALUES(?,?,?,?,?);
EOD;
        $insertUserStmt = $conn->prepare($sql);

        foreach ($data['users'] as $user) {
            $item = [
                $user['name'],
                $user['email'],
                $user['username'],
                $user['personKey'],
                $user['roles'],
            ];
            $insertUserStmt->execute($item);
        }
        $provider = new ProjectUserProvider(
            'AYSONationalGames2016',
            $conn, $conn, []
        );
        $user = $provider->loadUserByUsername('ahundiak');
        $this->assertEquals('ahundiak', $user['username']);
        $this->assertEquals('ahundiak@fake.com', $user['email']);
        $this->assertEquals('ROLE_USER', $user['roles'][0]);


        $userId = $user['id'];
        $user = new ProjectUser();
        $user['id'] = $userId;

        $user = $provider->refreshUser($user);
        $this->assertEquals('Art Hundiak', $user['name']);
        $this->assertEquals('AYSONationalGames2016', $user['projectKey']);
        $this->assertNull($user['registered']);

        $sql = <<<EOD
INSERT INTO projectPersons(projectKey,personKey,name,email,registered) VALUES(?,?,?,?,?);
EOD;
        $insertProjectPersonStmt = $conn->prepare($sql);

        foreach ($data['projectPersons'] as $projectPerson) {
            $item = [
                $projectPerson['projectKey'],
                $projectPerson['personKey'],
                $projectPerson['name'],
                $projectPerson['email'],
                $projectPerson['registered'],
            ];
            $insertProjectPersonStmt->execute($item);
        }
        $user = $provider->loadUserByUsername('ahundiak');

        $this->assertEquals(1, $user['registered']);
        $this->assertCount(1, $user['roles']);

        $sql = <<<EOD
INSERT INTO projectPersonRoles(projectPersonId,role,active) VALUES(?,?,?);
EOD;
        $insertProjectPersonRoleStmt = $conn->prepare($sql);

        $projectPersonRepository = new ProjectPersonRepository($conn);

        foreach ($data['projectPersons'] as $projectPersonData) {
            if (isset($projectPersonData['roles'])) {
                $projectPerson = $projectPersonRepository->find($projectPersonData['projectKey'],$projectPersonData['personKey']);
                $this->assertNotNull($projectPerson);
                $projectPersonId = $projectPerson['id'];
                foreach ($projectPersonData['roles'] as $roleData) {
                    $insertProjectPersonRoleStmt->execute([
                        $projectPersonId,
                        $roleData['role'],
                        $roleData['active'],
                    ]);
                }
            }
        }
        $user = $provider->loadUserByUsername('ahundiak');

        $this->assertCount(2, $user['roles']);

        $this->assertEquals('ROLE_REFEREE', $user['roles'][1]);
    }
}