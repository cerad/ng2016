<?php

namespace AppBundle\Action\Project\Person\Admin\Update;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminUpdateTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', 'http://local.ng2016/project/person/admin/update/AYSONationalGames2016.C4AF1DBD-4945-4269-97A6-E2E203319D58');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
