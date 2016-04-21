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
}