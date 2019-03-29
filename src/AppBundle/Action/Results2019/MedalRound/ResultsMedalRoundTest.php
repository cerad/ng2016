<?php

namespace AppBundle\Action\Results2019\MedalRound;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsMedalRoundTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results/medalround?division=U14B');
        //$crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
