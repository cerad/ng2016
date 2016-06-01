<?php

namespace AppBundle\Action\Schedule2016\Game;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleGameTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/game');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportText()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/game.txt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportExcel()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/game.xls');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
