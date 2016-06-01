<?php
namespace AppBundleAction\Project\User;

use AppBundle\AbstractTestDatabase;

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
        $passwordToken = $projectUser['passwordToken'] = $projectUserRepository->generateToken();
        $emailToken    = $projectUser['emailToken']    = $projectUserRepository->generateToken();

        $roles = $projectUser['roles'];
        $roles[] = 'ROLE_VAMPIRE_SLAYER';
        $projectUser['roles'] = $roles;
        
        $projectUser = $projectUserRepository->save($projectUser);
        $this->assertGreaterThan(0,$projectUser['id']);

        $projectUser = $projectUserRepository->find('buffy');
        $this->assertEquals('salted',$projectUser['salt']);

        $projectUser = $projectUserRepository->find($passwordToken);
        $this->assertEquals($passwordToken,$projectUser['passwordToken']);

        $projectUser = $projectUserRepository->find($emailToken);
        $this->assertEquals($emailToken,$projectUser['emailToken']);

        $this->assertEquals('ROLE_USER',$projectUser['roles'][0]);
        $this->assertEquals('ROLE_VAMPIRE_SLAYER',$projectUser['roles'][1]);

        $projectUser = $projectUserRepository->find(null);
        $this->assertNull($projectUser);
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
