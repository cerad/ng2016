<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Common\DatabaseInitTrait;

use PHPUnit_Framework_TestCase;

class ScheduleFinderTest extends PHPUnit_Framework_TestCase
{
    use DatabaseInitTrait;

    private $gameDatabaseKey = 'database_name_ng2016games';
    private $poolDatabaseKey = 'database_name_ng2016games';

    private function createFinder()
    {
        $gameConn = $this->getConnection($this->gameDatabaseKey);
        $poolConn = $this->getConnection($this->poolDatabaseKey);

        return new ScheduleFinder($gameConn,$poolConn);
    }
    public function test1()
    {
        $finder = $this->createFinder();
        
        $criteria = [
            'projectKeys' => ['AYSONationalGames2014'],
            'programs'    => ['Core'],
            'divisions'   => ['U14B'],
            'poolTypes'   => ['PP'],
            'dates'       => ['2014-07-03'],
        ];
        $games = $finder->findGames($criteria);

        $this->assertInternalType('array',$games);
        $this->assertCount(24,$games);

        //var_dump($games[20]);
    }
}