<?php

namespace Tests\AppBundle\Action\Results\MedalRound;

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
            $crawler->filter('html:contains("GS=Goals Scored, SP=Sportsmanship, YC=Caution, RC=Sendoff, TE=Total Ejections")')->count()
        );
    }
    public function testU16G_Core()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/medalround?project=AYSONationalGames2014&ages=U16&genders=G&programs=Core');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Medal Round Results : AYSO U16G Core")')->count()
        );

    }
}
