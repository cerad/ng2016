<?php

namespace Tests\AppBundle\Action\Results\FinalStandings;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsFinalControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/results/final');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Congratulations to all ")')->count()
        );
    }
}
