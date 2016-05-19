<?php

namespace AppBundle\Action\Schedule2016\FieldMap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleFieldMapTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/fieldmap');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
