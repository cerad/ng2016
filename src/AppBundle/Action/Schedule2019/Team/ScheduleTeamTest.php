<?php

namespace AppBundle\Action\Schedule2019\Team;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleTeamTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/team');
        //echo $crawler->html();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportText()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/team.txt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportExcel()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule/team.xls');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
