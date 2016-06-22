<?php
namespace AppBundle\Action\Game;

use AppBundle\Common\DatabaseTrait;
use AppBundle\Common\DirectoryTrait;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class GameUpdaterTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    use DirectoryTrait;

    private $gameDatabaseKey = 'database_name_test';

    /** @var  Connection */
    private $gameConn;
    
    private $projectId = 'OlympicsFootball2016';
    private $gameNumber = 1;

    /**
     * @return GameFinder
     */
    private function createGameFinder()
    {
        if (!$this->gameConn) {
            $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        return new GameFinder($this->gameConn,$this->gameConn);
    }
    /**
     * @return GameUpdater
     */
    private function createGameUpdater()
    {
        if (!$this->gameConn) {
            $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        return new GameUpdater($this->gameConn);
    }
    public function testLoad()
    {
        // Just to create the connections
        $this->createGameUpdater();

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($this->gameConn, $schemaFile);

        $projectId  = $this->projectId;
        $gameNumber = $this->gameNumber;
        $gameId = $projectId . ':' . $gameNumber;

        $game = [
            'gameId'      => $gameId,
            'projectId'   => $projectId,
            'gameNumber'  => $gameNumber,
        ];
        $this->gameConn->insert('games',$game);

        // Game Teams
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

        // Pool Teams
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

        // Officials
        foreach([1,2,3] as $slot) {
            $gameOfficial = [
                'gameOfficialId' => $gameId . ':' . $slot,
                'projectId'      => $projectId,
                'gameId'         => $gameId,
                'gameNumber'     => $gameNumber,
                'slot'           => $slot,
            ];
            $this->gameConn->insert('gameOfficials',$gameOfficial);
        }
    }
    public function testChangeGameNumber()
    {
        $updater = $this->createGameUpdater();

        $projectId     = $this->projectId;
        $gameNumberOld = $this->gameNumber;
        $gameNumberNew = $this->gameNumber + 2;

        $updater->changeGameNumber($projectId,$gameNumberOld,$gameNumberNew);

        $finder = $this->createGameFinder();
        $game = $finder->findGame($projectId,$gameNumberNew);
        $this->assertInstanceOf(Game::class,$game);

        $updater->deleteGame($projectId,$gameNumberNew);
        $game = $finder->findGame($projectId,$gameNumberNew);
        $this->assertNull($game);

    }
}
