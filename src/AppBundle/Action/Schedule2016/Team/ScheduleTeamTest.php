<?php

namespace AppBundle\Action\Schedule2016\Team;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScheduleTeamTest extends WebTestCase
{
    public function testHeartbeat()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/team');
        //echo $crawler->html();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportText()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/team.txt');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testExportExcel()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/schedule2016/team.xls');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
