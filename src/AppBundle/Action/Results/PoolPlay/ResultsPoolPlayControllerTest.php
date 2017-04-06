<?php

namespace AppBundle\Action\Results\PoolPlay;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsPoolPlayControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/poolplay');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Winning Percent")')->count()
        );
    }
    public function testU14B_D()
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/results/poolplay');

        $crawler = $client->request('GET', '/results/poolplay?poolKey=U14BCorePPD');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Pool Team Standings : U14-B Pool Play D")')->count()
        );

    }
}
