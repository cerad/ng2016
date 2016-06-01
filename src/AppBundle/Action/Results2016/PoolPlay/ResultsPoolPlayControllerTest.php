<?php

namespace AppBundle\Action\Results2016\PoolPlay;

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

        $crawler = $client->request('GET', '/results/poolplay');
        
        $buttonCrawlerNode = $crawler->selectButton('submit');
var_dump($crawler);die();
        $form = $buttonCrawlerNode->form();
        
        $client->submit($form, array(
            'projectId' => 'AYSONationalGames2014'
        ));

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Pool Team Standings : U14-B Core PP D")')->count()
        );

    }
}
