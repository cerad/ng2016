<?php

namespace AppBundle\Action\Schedule\FieldMap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleFieldMapTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();
        
        $client->request('GET', '/schedule/field_map');
        
        $this->assertContains('master_field_layout.pdf', $client->getResponse()->headers->get('Content-Disposition'));
    }
}
