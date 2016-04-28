<?php

namespace AppBundle\Action\Schedule2016\Game;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleGameTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/game');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        
    }
}
