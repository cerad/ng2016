<?php

namespace AppBundle\Action\Schedule2016\Official;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleOfficialTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/official');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
