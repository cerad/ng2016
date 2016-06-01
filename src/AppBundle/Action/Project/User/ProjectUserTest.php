<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Action\Project\User\ProjectUser;

class ProjectUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $user = new ProjectUser();

        $this->assertTrue($user['enabled']);
    }
}
