<?php

namespace AppBundle\Action\Results2016\Sportsmanship;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsSportsmanshipTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results2016/sportsmanship?division=U14B');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
