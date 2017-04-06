<?php
namespace AppBundle\Action\GameReport;

use AppBundle\Common\DatabaseTrait;
use AppBundle\Common\DirectoryTrait;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class GameReportRepositoryTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    use DirectoryTrait;

    private $gameDatabaseKey = 'database_name_test';

    /** @var  Connection */
    private $gameConn;
    
    private $projectId = 'OlympicsFootball2016';
    private $gameNumber = 1;

    private function createGameReportRepository()
    {
        if (!$this->gameConn) {
             $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        return new GameReportRepository($this->gameConn);
    }
    public function testLoad()
    {
        // Just to create the connections
        $this->createGameReportRepository();

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($this->gameConn, $schemaFile);

        $projectId  = $this->projectId;
        $gameNumber = $this->gameNumber;
        $gameId = $projectId . ':' . $gameNumber;

        $game = [
            'gameId'      => $gameId,
            'projectId'   => $projectId,
            'gameNumber'  => $gameNumber,
            'state'       => 'Published',
            'reportText'  => null,
            'reportState' => 'Initial',
        ];
        $this->gameConn->insert('games',$game);

        $gameTeam = [
            'gameTeamId' => $gameId . ':1',
            'projectId'  => $projectId,
            'gameId'     => $gameId,
            'gameNumber' => $gameNumber,
            'slot'       => 1,
            'poolTeamId' => 'PTA1',
        ];
        $this->gameConn->insert('gameTeams',$gameTeam);
        $gameTeam['gameTeamId'] = $gameId . ':2';
        $gameTeam['slot']       = 2;
        $gameTeam['poolTeamId'] = 'PTA2';
        $this->gameConn->insert('gameTeams',$gameTeam);

        $poolTeam = [
            'projectId'   => $projectId,
            'poolTeamId'  => 'PTA1',
            'poolTeamKey' => 'PTA1',
            'poolTypeKey' => 'PP',
            'poolKey'     => 'PTA',
            'regTeamName' => 'Name A1',
        ];
        $this->gameConn->insert('poolTeams',$poolTeam);
        $poolTeam['poolTeamId']  = 'PTA2';
        $poolTeam['poolTeamKey'] = 'PTA2';
        $poolTeam['regTeamName'] = 'Name A2';

        $this->gameConn->insert('poolTeams',$poolTeam);
    }
    public function testFindGameReport()
    {
        $repo = $this->createGameReportRepository();

        $projectId  = $this->projectId;
        $gameNumber = $this->gameNumber;

        $gameReport = $repo->findGameReport($projectId,$gameNumber);

        $this->assertInternalType('integer',$gameReport->gameNumber);
        $this->assertEquals($gameNumber,$gameReport->gameNumber);

        $homeTeam = $gameReport->homeTeam;
        $this->assertEquals('Name A1',$homeTeam->regTeamName);
        $this->assertEquals('PTA1',   $homeTeam->poolTeamKey);
        $this->assertNull($homeTeam->pointsScored);

        $this->assertNull($homeTeam->misconduct->playerEjections);

        $awayTeam = $gameReport->awayTeam;
        $this->assertEquals('Name A2',$awayTeam->regTeamName);
    }
    public function testUpdateGameReport()
    {
        $repo = $this->createGameReportRepository();

        $projectId  = $this->projectId;
        $gameNumber = $this->gameNumber;

        $gameReport = $repo->findGameReport($projectId,$gameNumber);

        $gameReport->reportState = 'Entered';

        $homeTeam = $gameReport->homeTeam;
        $awayTeam = $gameReport->awayTeam;

        $homeTeam->pointsScored = 3; $awayTeam->pointsAllowed = 3;
        $awayTeam->pointsScored = 1; $homeTeam->pointsAllowed = 1;

        $awayTeam->misconduct->playerEjections = 1;
        
        $repo->updateGameReport($gameReport);

        $gameReport = $repo->findGameReport($projectId,$gameNumber);

        $this->assertEquals('Entered',$gameReport->reportState);
        $awayTeam = $gameReport->awayTeam;
        $this->assertEquals(1,$awayTeam->pointsScored);
        $this->assertEquals(1,$awayTeam->misconduct->playerEjections);
    }
    public function testPointsCalculator()
    {
        $repo = $this->createGameReportRepository();

        $projectId  = $this->projectId;
        $gameNumber = $this->gameNumber;

        $gameReport = $repo->findGameReport($projectId,$gameNumber);

        $pointsCalculator = new GameReportPointsCalculator();
        $pointsCalculator($gameReport);

        $homeTeam = $gameReport->homeTeam;
        $awayTeam = $gameReport->awayTeam;

        $this->assertEquals( 8,$homeTeam->pointsEarned);
        $this->assertEquals(-1,$awayTeam->pointsEarned);

    }
}
