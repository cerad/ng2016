<?php

namespace AppBundle\Action\Results2019\Sportsmanship;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsSportsmanshipTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results/sportsmanship?division=U14B');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
