<?php
namespace AppBundle\Action\Project\Person;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRole;
use PHPUnit_Framework_TestCase;

class ProjectPersonTest extends PHPUnit_Framework_TestCase
{
    public function testNewProjectPerson()
    {
        $projectKey = 'ProjectKey';
        $personKey  = 'zander-0001';
        $name       = 'Zander Harris';
        $email      = 'zander@sunnydale.tv';

        $person = new ProjectPerson();
        $person->init($projectKey,$personKey,$name,$email);

        $this->assertEquals($name,$person->name);

        $personArray = $person->toArray();
        $this->assertEquals($personKey,$personArray['personKey']);

        $personArray = [
            'id'         => 1,
            'projectKey' => $projectKey,
            'email'      => $email,
        ];
        $person = new ProjectPerson();
        $person = $person->fromArray($personArray);

        $this->assertEquals(1,$person['id']);
        $this->assertEquals($email,$person->email);
    }
    public function testNewProjectPersonRole()
    {
        $person = new ProjectPerson();

        $personRole = new ProjectPersonRole();
        $personRole->init('ROLE_REFEREE','Advanced');
        $this->assertEquals('Advanced',$personRole->badge);

        $person->addRole($personRole);

        $this->assertTrue ($person->hasRole('ROLE_REFEREE'));
        $this->assertFalse($person->hasRole('ROLE_REFEREE_XXX'));

        // And here we start to get into wanting to create temp objects
        $this->assertEquals('Advanced',$person->getRole('ROLE_REFEREE')->badge);
        
        $personArray = $person->toArray();
        $this->assertEquals('ROLE_REFEREE',$personArray['roles']['ROLE_REFEREE']['role']);

        $person = new ProjectPerson();
        $person = $person->fromArray($personArray);
        $this->assertEquals('Advanced',$person->getRole('ROLE_REFEREE')->badge);

//        $this->assertEquals('Advanced',$person->getRefereeBadge());

        $person = new ProjectPerson();
        $this->assertNull($person->getRefereeBadge());
    }
}
