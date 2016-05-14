<?php

namespace AppBundle\Action\Results2016\MedalRound;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsMedalRoundTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/results2016/medalround?division=U14B');
        //$crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
