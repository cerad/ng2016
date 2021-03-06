<?php

namespace AppBundle\Action\Results\MedalRound;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsFinalRoundTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results/final');
        //$crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
