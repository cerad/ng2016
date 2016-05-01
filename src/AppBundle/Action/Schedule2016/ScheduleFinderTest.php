<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Common\DatabaseTrait;

use PHPUnit_Framework_TestCase;

class ScheduleFinderTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;

    private $gameDatabaseKey = 'database_name_ng2016games';
    private $poolDatabaseKey = 'database_name_ng2016games';
    private $teamDatabaseKey = 'database_name_ng2016games';

    private function createFinder()
    {
        $gameConn = $this->getConnection($this->gameDatabaseKey);
        $poolConn = $this->getConnection($this->poolDatabaseKey);
        $teamConn = $this->getConnection($this->teamDatabaseKey);

        return new ScheduleFinder($gameConn,$poolConn,$teamConn);
    }
    public function testFindGames()
    {
        $finder = $this->createFinder();
        
        $criteria = [
            'projectKeys' => ['AYSONationalGames2014'],
            'programs'    => ['Core'],
            'divisions'   => ['U14B'],
            'poolTypes'   => ['PP'],
            'dates'       => ['2014-07-03'],
        ];
        $games = $finder->findGames($criteria,true);

        $this->assertInternalType('array',$games);
        $this->assertCount(24,$games);

        $game = $games[8];
        $this->assertInternalType('object',$game);
        $this->assertEquals('FD3',$game->fieldName);

        $this->assertEquals('Thu',            $game->dow);
        $this->assertEquals('11:45 AM',       $game->time);
        $this->assertEquals('U14-B Core PP C',$game->poolView);

        $this->assertEquals('#15 01-U-0624 Nunez',$game->homeTeam->name);
        
        $this->assertEquals('C6',$game->awayTeam->poolTeamSlotView);

    }
    public function testFindProjectTeams()
    {
        $finder = $this->createFinder();

        $criteria = [
            'projectKeys' => ['AYSONationalGames2016'],
            'programs'    => ['Core'],
            'divisions'   => ['U14B'],
        ];
        $teams = $finder->findProjectTeams($criteria);
        $teams = array_values($teams);

        $this->assertCount(24,$teams);

        $team = $teams[2];
        $this->assertEquals('#03',trim($team->name));
    }
}