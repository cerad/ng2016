<?php

namespace AppBundle\Action\Results2019\PoolPlay;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsPoolPlayTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results/poolplay?division=U14B');
        $crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
