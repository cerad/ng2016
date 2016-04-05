<?php
namespace Tests\AppBundle\Action\Project\User;

use AppBundle\Action\Project\User\ProjectUser;

class ProjectUsertest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $user = new ProjectUser();

        $this->assertTrue($user['enabled']);
    }
}