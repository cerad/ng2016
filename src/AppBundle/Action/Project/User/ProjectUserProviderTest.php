<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Action\Project\Person\ProjectPersonRepository;
use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\Project\User\ProjectUserProvider;

use AppBundle\AbstractTestDatabase;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

class ProjectPersonRepositoryTest extends AbstractTestDatabase
{
    protected $users;

    public function setUp()
    {
        parent::setUp();

        $this->users = [
            'assignorx' => [
                'id'    =>  -2,
                'name'  => 'Assignor(x)',
                'role'  => 'ROLE_ASSIGNOR',
                'email' => 'assignor@fake.com',
            ],
        ];
    }
    public function testLoadAndRefresh()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);

        $sql = <<<EOD
INSERT INTO users (name,email,username,personKey) VALUES(?,?,?,?);
EOD;
        $insertUserStmt = $conn->prepare($sql);
        $insertUserStmt->execute(['Buffy Summers', 'buffy@sunnydale.tv', 'buffy', 'buffy-0001']);
        $insertUserStmt->execute(['Angel',         'angel@la.tv',        'angel', 'angel-0001']);

        $projectUserProvider = new ProjectUserProvider('ProjectKey',$conn,$conn,$this->users);

        $user = $projectUserProvider->loadUserByUsername('buffy@sunnydale.tv');
        $this->assertEquals('buffy-0001',$user['personKey']);

        $user = $projectUserProvider->loadUserByUsername('angel');
        $this->assertEquals('angel-0001',$user['personKey']);
        $angelId = $user['id'];

        // Refresh user tests
        $user = new ProjectUser();
        $user['id'] = $angelId;
        $user = $projectUserProvider->refreshUser($user);
        $this->assertEquals('Angel',$user['name']);

        $user = new ProjectUser();
        $user['id'] = -2;
        $user = $projectUserProvider->refreshUser($user);
        $this->assertEquals('assignor@fake.com',$user['email']);

    }
    public function testProjectInterface()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);
        $projectKey = 'ProjectKey';

        $sql = <<<EOD
INSERT INTO users (name,email,username,personKey) VALUES(?,?,?,?);
EOD;
        $insertUserStmt = $conn->prepare($sql);
        $insertUserStmt->execute(['Buffy Summers', 'buffy@sunnydale.tv', 'buffy', 'buffy-0001']);
        $insertUserStmt->execute(['Angel',         'angel@la.tv',        'angel', 'angel-0001']);

        $projectUserProvider = new ProjectUserProvider($projectKey,$conn,$conn,$this->users);

        $user = $projectUserProvider->loadUserByUsername('buffy@sunnydale.tv');
        $this->assertNull($user['registered']);
        $roles = $user['roles'];
        $this->assertCount (1,$roles);
        $this->assertEquals('ROLE_USER', $roles[0]);

        $sql = <<<EOD
INSERT INTO projectPersons(projectKey,personKey,name,email,registered) VALUES(?,?,?,?,?);
EOD;
        $insertProjectPersonStmt = $conn->prepare($sql);
        $insertProjectPersonStmt->execute([
            $projectKey,
            'buffy-0001',
            'Buffy Registered',
            'buffy@registered.tv',
            true,
        ]);
        $user = $projectUserProvider->loadUserByUsername('buffy');
        $this->assertTrue($user['registered']);
        $this->assertCount(1, $user['roles']);

        // Need this to get id to append roles
        $projectPersonRepository = new ProjectPersonRepository($conn);
        $projectPerson = $projectPersonRepository->find($projectKey,'buffy-0001');
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
        $insertProjectPersonRoleStmt->execute([
            $projectPerson['id'],
            'ROLE_ASSESSOR',
            false, // Add it but ignore the role
        ]);
        // Picks up the project specific roles, very cool
        $user = $projectUserProvider->loadUserByUsername('buffy');
        $roles = $user['roles'];
        $this->assertCount(2, $roles);
        $this->assertEquals('ROLE_REFEREE', $roles[1]);
    }
}
