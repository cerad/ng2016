<?php
namespace Tests\AppBundleAction\Project\User;

use Tests\AppBundle\AbstractTestDatabase;

use AppBundle\Action\Project\User\ProjectUserRepository;

use AppBundle\Action\Project\Person\ProjectPersonRepository;
use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\Project\User\ProjectUserProvider;

//  Doctrine\DBAL\Connection;

class ProjectUserRepositoryTest extends AbstractTestDatabase
{
    public function testUnique()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);

        $sql = <<<EOD
INSERT INTO users (name,email,username,personKey) VALUES(?,?,?,?);
EOD;
        $insertUserStmt = $conn->prepare($sql);
        $insertUserStmt->execute([
            'Buffy Summers',
            'buffy@sunnydale.tv',
            'buffy',
            'buffy-0001',
        ]);

        $projectUserRepository = new ProjectUserRepository($conn);

        $this->assertTrue ($projectUserRepository->isEmailUnique('angel@la.tv'));
        $this->assertFalse($projectUserRepository->isEmailUnique('buffy@sunnydale.tv'));
        $this->assertFalse($projectUserRepository->isEmailUnique('buffy'));

        $this->assertTrue ($projectUserRepository->isUsernameUnique('angel'));
        $this->assertFalse($projectUserRepository->isUsernameUnique('buffy'));

        $username = $projectUserRepository->generateUniqueUsernameFromEmail('angel@la.tv');
        $this->assertEquals('angel',$username);

        $username = $projectUserRepository->generateUniqueUsernameFromEmail('buffy@sunnydale.tv');
        $this->assertEquals('buffy2',$username);
    }
}