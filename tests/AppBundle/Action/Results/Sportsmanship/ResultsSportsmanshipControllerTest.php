<?php

namespace Tests\AppBundle\Action\Results\Sportsmanship;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsSportsmanshipControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/sportsmanship');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("In the event of a tie, all team members and coaches will receive medals.")')->count()
        );
    }
    public function testU14B_D()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/sportsmanship?project=AYSONationalGames2014&ages=U16&genders=B&programs=Core');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Sportsmanship Standings : AYSO U16B Core")')->count()
        );

    }
}
