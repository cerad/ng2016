<?php

namespace Tests\AppBundle\Action\Results\PoolPlay;

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

        $crawler = $client->request('GET', '/results/poolplay?project=AYSONationalGames2014&ages=U14&genders=B&programs=Core&pools=D');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Pool Team Standings : U14B Core PP D")')->count()
        );

    }
}
