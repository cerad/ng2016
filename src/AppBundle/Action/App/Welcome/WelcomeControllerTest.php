<?php

namespace AppBundle\Action\App\Welcome;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WelcomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/welcome');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        //$this->assertContains('Welcome to NG2016', $crawler->html());
    }
}
