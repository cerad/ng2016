<?php

namespace AppBundle\Action\Schedule2016\FieldMap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleFieldMapTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/field_map');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
