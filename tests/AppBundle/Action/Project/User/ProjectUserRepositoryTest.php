<?php
namespace Tests\AppBundleAction\Project\User;

use Tests\AppBundle\AbstractTestDatabase;

use AppBundle\Action\Project\User\ProjectUserRepository;

class ProjectUserRepositoryTest extends AbstractTestDatabase
{
    public function testCreateSaveFind()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);
        
        $projectUserRepository = new ProjectUserRepository($conn);
        
        $projectUser = $projectUserRepository->create('buffy-0001','Buffy Summers','buffy','buffy@sunnydale.tv');
        $projectUser['salt'] = 'salted';
        
        $projectUser = $projectUserRepository->save($projectUser);
        $this->assertGreaterThan(0,$projectUser['id']);

        $projectUser = $projectUserRepository->find('buffy');
        $this->assertEquals('salted',$projectUser['salt']);
    }
    public function testUnique()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);

        $projectUserRepository = new ProjectUserRepository($conn);

        $projectUser = $projectUserRepository->create('buffy-0001','Buffy Summers','buffy','buffy@sunnydale.tv');
        $projectUser = $projectUserRepository->save  ($projectUser);
        $this->assertGreaterThan(0,$projectUser['id']);

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