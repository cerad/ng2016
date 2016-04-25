<?php
namespace Tests\AppBundle\Action\Project\Person;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Tests\AppBundle\AbstractTestDatabase;

class ProjectPersonRepositoryV2Test extends AbstractTestDatabase
{
    public function testCreate()
    {
        $repo = new ProjectPersonRepositoryV2($this->conn);
        
        $person = $repo->create('ProjectKey','PersonKey','Willow Rosenburg','willow@sunnydale.tv');
        $this->assertEquals('willow@sunnydale.tv',$person->email);
        
        $personRole = $repo->createRole('ROLE_WITCH','Level 5');
        $this->assertEquals('ROLE_WITCH',$personRole->role);

    }
    public function testFind()
    {
        $this->resetDatabase($this->conn);
        
        $repo = new ProjectPersonRepositoryV2($this->conn);

        $projectKey = 'ProjectKey';
        $personKey  = 'zander-0001';

        $person = $repo->create($projectKey,$personKey,'Zander Harris','Email');
        $this->assertEquals($projectKey,$person['projectKey']);
        $this->assertInternalType('array',$person['roles']);

        $personRole = $repo->createRole('ROLE_PATSY','Whatever');
        $person->addRole($personRole);
        
        $person = $repo->save($person);
        $this->assertGreaterThan(0,$person['id']);

        $person = $repo->find($projectKey,$personKey);
        $this->assertEquals($personKey,$person['personKey']);
        $this->assertEquals('Whatever',$person->getRole('ROLE_PATSY')->badge);

        $name = $repo->generateUniqueName($projectKey,'Zander Harris');
        $this->assertEquals('Zander Harris(2)',$name);
    }
    public function testFindByProjectKey()
    {
        $this->resetDatabase($this->conn);

        $repo = new ProjectPersonRepositoryV2($this->conn);

        $projectKey = 'ProjectKey';

        // Fixtures
        $person = $repo->create($projectKey,'zander-0001','Zander Harris','Email');
        $repo->save($person);

        $person = $repo->create($projectKey,'buffy-0001','Buffy Summers','Email');
        $personRole = $repo->createRole('ROLE_VAMPIRE_SLAYER','Chosen');
        $person->addRole($personRole);

        $repo->save($person);
        $person = $repo->create($projectKey,'willow-0001','Willow Rosenburg','Email');
        $repo->save($person);
        
        $persons = $repo->findByProjectKey($projectKey,null,null);
        $this->assertCount(3,$persons);

        $this->assertEquals('Chosen',$persons[0]->getRole('ROLE_VAMPIRE_SLAYER')->badge);

        $persons = $repo->findByProjectKey($projectKey,'Willow',null);
        $this->assertCount(1,$persons);

    }
}