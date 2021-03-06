<?php

namespace AppBundle\Action\GameReport\Update;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameReportUpdateTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        // This is protected
        $crawler = $client->request('GET', '/game/report/update/AYSONationalOpenCup2018/13402');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
