<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Common\DatabaseTrait;
use AppBundle\Common\DirectoryTrait;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class ScheduleFinderTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    use DirectoryTrait;

    private $gameDatabaseKey = 'database_name_test';
    private $regTeamDatabaseKey = 'database_name_test'; // Registered teams

    /** @var  Connection */
    private $gameConn;

    /** @var  Connection */
    private $regTeamConn;

    private $projectId = 'OlympicsFootball2016';

    private function createScheduleFinder()
    {
        if (!$this->gameConn) {
             $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        if (!$this->regTeamConn) {
             $this->regTeamConn = $this->getConnection($this->regTeamDatabaseKey);
        }
        return new ScheduleFinder($this->gameConn,$this->regTeamConn);
    }
    public function sestLoad()
    {
        // Just to create the connections
        $this->createScheduleFinder();

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($this->gameConn, $schemaFile);

        $projectId = $this->projectId;

        // Create some registered teams
        $programs = ['Core'];
        $genders  = ['B','G'];
        $ages     = ['U12','U14'];
        $regTeams = [];
        foreach($programs as $program) {
            foreach($ages as $age) {
                foreach($genders as $gender) {
                    $division = $age . $gender;
                    foreach([1,2,3,4,5,6] as $teamNumber) {
                        $teamKey   = sprintf('%s%s#%02d',$division,$program,$teamNumber);
                        $teamName  = sprintf('#%02d',$teamNumber);
                        $regTeamId = $projectId . ':' . $teamKey;
                        $regTeam = [
                            'regTeamId'  => $regTeamId,
                            'projectId'  => $projectId,
                            'teamKey'    => $teamKey,
                            'teamNumber' => $teamNumber,
                            'teamName'   => $teamName,
                            'program'    => $program,
                            'gender'     => $gender,
                            'age'        => $age,
                            'division'   => $division,
                        ];
                        $regTeams[$regTeamId] = $regTeam;
                        $this->regTeamConn->insert('regTeams',$regTeam);
                    }
                }
            }
        }
        // Create some pool teams
        foreach($programs as $program) {
            foreach ($ages as $age) {
                foreach ($genders as $gender) {
                    $division = $age . $gender;
                    $teamNumber = 0;
                    $poolType = 'PP';
                    foreach(['A','B'] as $poolName) {
                        foreach([1,2,3] as $poolTeamNumber) {

                            $poolKey =  sprintf('%s%s%s%s%s',    $age,$gender,$program,$poolType,$poolName);
                            $poolView = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolName);

                            $poolTeamKey =  sprintf('%s%s%s%s%s%s',    $age,$gender,$program,$poolType,$poolName,$poolTeamNumber);
                            $poolTeamView = sprintf('%s-%s %s %s %s%s',$age,$gender,$program,$poolType,$poolName,$poolTeamNumber);

                            $poolTeamId = $projectId . ':' . $poolTeamKey;

                            $teamNumber++;
                            $teamKey   = sprintf('%s%s#%02d',$division,$program,$teamNumber);
                            $teamName  = sprintf('#%02d',$teamNumber);
                            $regTeamId = $projectId . ':' . $teamKey;

                            $poolTeam = [
                                'poolTeamId'  => $poolTeamId,
                                'projectId'   => $projectId,

                                'poolKey'     => $poolKey,
                                'poolTypeKey' => $poolType,
                                'poolTeamKey' => $poolTeamKey,

                                'poolView'     => $poolView,
                                'poolTypeView' => $poolType,
                                'poolTeamView' => $poolTeamView,
                                'poolTeamSlotView' => $poolName . $poolTeamNumber,

                                'program'    => $program,
                                'gender'     => $gender,
                                'age'        => $age,
                                'division'   => $division,

                                'regTeamId'  => $regTeamId,
                                'teamName'   => $teamName,
                            ];
                            $this->gameConn->insert('poolTeams',$poolTeam);
                        }
                    }
                }
            }
        }

        // And some games
        $fieldSlots = [
            [1, 'Thu', '08:00', 'A1', 'A2', 'G'], [2, 'Thu', '08:00', 'A1', 'A2', 'G'],
            [1, 'Thu', '12:00', 'A2', 'A3', 'G'], [2, 'Thu', '11:00', 'A2', 'A3', 'G'],
            [1, 'Thu', '16:00', 'A3', 'A1', 'G'], [2, 'Thu', '09:15', 'A3', 'A1', 'G'],

            [1, 'Thu', '10:00', 'A1', 'A2', 'B'], [2, 'Thu', '10:00', 'A1', 'A2', 'B'],
            [1, 'Thu', '14:00', 'A2', 'A3', 'B'], [2, 'Thu', '14:00', 'A2', 'A3', 'B'],
            [1, 'Thu', '18:00', 'A3', 'A1', 'B'], [2, 'Thu', '18:15', 'A3', 'A1', 'B'],
        ];
        $gameNumber = 0;
        foreach($programs as $program) {
            foreach ($ages as $age) {
                foreach($genders as $gender) {
                    foreach ($fieldSlots as $fieldSlot) {
                        list($fieldNumber, $dow, $time, $home, $away, $fieldSlotGender) = $fieldSlot;
                        if ($fieldSlotGender === $gender) {
                            $gameNumber++;
                            $this->initGame($projectId, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away);
                        }
                    }
                }
            }
        }
    }
    /* Copied from the init program */
    private function initGame($projectId, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away)
    {
        $dates = [
            'Wed' => '2016-07-06',
            'Thu' => '2016-07-07',
            'Fri' => '2016-07-08',
            'Sat' => '2016-07-09',
            'Sun' => '2016-07-10',
        ];
        $start = $dates[$dow] . ' ' . $time . ':00';

        $lengths = [
            'U10' => 40 +  5,
            'U12' => 50 +  5,
            'U14' => 50 + 10,
            'U16' => 60 + 10,
            'U19' => 60 + 10,
        ];
        // Add playing time to game entity?
        $finishDateTime = new \DateTime($start);
        $interval = sprintf('PT%dM',$lengths[$age]);
        $finishDateTime->add(new \DateInterval($interval));

        $gameId = $projectId . ':' . $gameNumber;
        $game = [
            'gameId'     => $gameId,
            'projectId'  => $projectId,
            'gameNumber' => $gameNumber,
            'fieldName'  => $age . ' ' . $fieldNumber,
            'venueName'  => 'Rio',

            'start'  => $start,
            'finish' => $finishDateTime->format('Y-m-d H:i:s'),

            'state'       => 'Published',
            'status'      => 'Normal',
            'reportState' => 'Initial',
        ];
        $this->gameConn->insert('games',$game);

        // Game officials
        $isMedalRound = in_array(substr($home,0,2),['QF','SF','TF']);
        $gameOfficial = [
            'projectId'   => $projectId,
            'gameNumber'  => $gameNumber,
            'assignRole'  => $isMedalRound ? 'ROLE_ASSIGNOR' : 'ROLE_REFEREE',
            'assignState' => 'Open',
            'gameId'      => $gameId,
        ];
        foreach([1,2,3] as $slot) {
            $gameOfficial['gameOfficialId'] = $gameId . ':' . $slot;
            $gameOfficial['slot'] = $slot;
            $this->gameConn->insert('gameOfficials',$gameOfficial);
        }
        // Game Teams
        $gameTeam = [
            'gameId'      => $gameId,
            'projectId'   => $projectId,
            'gameNumber'  => $gameNumber,
            'poolTeamId'  => null,
        ];
        foreach([1,2] as $slot)
        {
            $team = $slot === 1 ? $home : $away;

            $poolTeamName = $isMedalRound ? $team : 'PP' . $team;

            $poolTeamId = sprintf('%s:%s%s%s%s', $projectId, $age, $gender, $program, $poolTeamName);

            $gameTeam['gameTeamId'] = $gameId . ':' . $slot;
            $gameTeam['slot']       = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('gameTeams',$gameTeam);
        }
    }
    
    public function testFindRegTeams()
    {
        $finder = $this->createScheduleFinder();
        $criteria = [
            'projectIds' => [$this->projectId],
            'genders'    => ['G'],
        ];
        $regTeams = $finder->findRegTeams($criteria);
        $this->assertCount(12,$regTeams);

        /** @var ScheduleRegTeam[] $regTeams */
        $regTeam = $regTeams[3];
        $this->assertEquals('U12G',$regTeam->division);
        $this->assertEquals('#04', $regTeam->teamName);
    }
    public function sestFindPoolTeams()
    {
        $finder = $this->createScheduleFinder();
        $criteria = [
            'projectIds' => [$this->projectId],
            'poolKeys'   => ['U14BCorePPA','U14BCorePPB'],
        ];
        $poolTeams = $finder->findPoolTeams($criteria);
        $this->assertCount(6,$poolTeams);

        /** @var SchedulePoolTeam[] $regTeams */
        $poolTeam = $poolTeams[3];
        $this->assertEquals('B',               $poolTeam->gender);
        $this->assertEquals('U14-B Core PP B1',$poolTeam->poolTeamView);
        $this->assertEquals('#04',             $poolTeam->name);
    }
    public function testFindGames()
    {
        $finder = $this->createScheduleFinder();

        $criteria = [
            'projectIds' => [$this->projectId],
            'divisions'  => ['U14B'],
        ];
        $games = $finder->findGames($criteria);
        $this->assertCount(6,$games);
        $game = $games[3];
        $this->assertInternalType('integer',$game->gameNumber);
        $this->assertEquals(16,$game->gameNumber);

        $homeTeam = $game->homeTeam;
        $this->assertEquals('U14B',$homeTeam->division);
        $this->assertEquals('#02', $homeTeam->teamName);
        $this->assertEquals('U14-B Core PP A',$homeTeam->poolView);
    }
}
