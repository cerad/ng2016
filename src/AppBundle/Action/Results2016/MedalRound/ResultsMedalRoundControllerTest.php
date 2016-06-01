<?php

namespace AppBundle\Action\Results2016\MedalRound;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsMedalRoundControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/medalround');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("GS=Goals Scored, YC=Caution, RC=Sendoff, TE=Total Ejections")')->count()
        );
    }
    public function testU16G_Core()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/medalround?division=U16G');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Medal Round Results : U16G Core")')->count()
        );

    }
}
