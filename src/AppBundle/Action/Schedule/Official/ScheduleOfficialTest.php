<?php

namespace AppBundle\Action\Schedule\Official;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleOfficialTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/official');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportText()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/official.txt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportExcel()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/official.xls');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
