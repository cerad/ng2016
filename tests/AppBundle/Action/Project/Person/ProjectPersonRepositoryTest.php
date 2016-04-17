<?php
namespace Tests\AppBundle\Action\Project\Person;

use AppBundle\Action\Project\ProjectFactory;

use AppBundle\Action\Project\Person\ProjectPersonRepository;

use Tests\AppBundle\AbstractTestDatabase;

class ProjectPersonRepositoryTest extends AbstractTestDatabase
{
    public function testFind()
    {
        $this->resetDatabase($this->conn);
        
        $projectPersonRepository = new ProjectPersonRepository($this->conn);

        $projectKey = 'ProjectKey';
        $personKey  = 'zander-0001';

        $projectPerson = $projectPersonRepository->create($projectKey,$personKey,'Zander Harris','Email');
        $this->assertEquals($projectKey,$projectPerson['projectKey']);
        $this->assertInternalType('array',$projectPerson['roles']);

        $projectPerson = $projectPersonRepository->save($projectPerson);
        $this->assertGreaterThan(0,$projectPerson['id']);

        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        $this->assertEquals($personKey,$projectPerson['personKey']);
    }
    public function testRole()
    {
        $projectPersonRepository = new ProjectPersonRepository($this->conn);

        $projectKey = 'ProjectKey';
        $personKey  = 'willow-0001';

        $projectPerson = $projectPersonRepository->create($projectKey,$personKey,'Willow Rosenburg','Email');
        $this->assertEquals($projectKey,$projectPerson['projectKey']);
        $this->assertInternalType('array',$projectPerson['roles']);

        $projectPersonRole = $projectPersonRepository->createRole('ROLE_REFEREE','National X');
        $projectPerson['roles']['ROLE_REFEREE'] = $projectPersonRole;

        $projectPerson = $projectPersonRepository->save($projectPerson);
        $this->assertGreaterThan(0,$projectPerson['id']);

        $projectPerson = $projectPersonRepository->find($projectKey,$personKey);
        $this->assertEquals($personKey,$projectPerson['personKey']);

        $projectPersonRole = $projectPerson['roles']['ROLE_REFEREE'];
        $this->assertEquals('National X',$projectPersonRole['badge']);
    }
}