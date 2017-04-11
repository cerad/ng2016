<?php

namespace AppBundle\Action\Project\Person\Admin\Update;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminUpdateTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();
        $client->followRedirects();

        // This is protected
        $crawler = $client->request('GET', '/project/person/admin/update/AYSONationalOpenCup2017.C4AF1DBD-4945-4269-97A6-E2E203319D58');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
