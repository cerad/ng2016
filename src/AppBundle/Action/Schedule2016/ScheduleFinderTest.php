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
    private $poolDatabaseKey = 'database_name_test';
    private $teamDatabaseKey = 'database_name_test'; // Registered teams

    /** @var  Connection */
    private $gameConn;

    /** @var  Connection */
    private $poolConn;

    /** @var  Connection */
    private $teamConn;

    private $projectKey = 'OlympicsFootball2016';

    private function createScheduleFinder()
    {
        if (!$this->gameConn) {
             $this->gameConn = $this->getConnection($this->gameDatabaseKey);
        }
        if (!$this->poolConn) {
             $this->poolConn = $this->getConnection($this->poolDatabaseKey);
        }
        if (!$this->teamConn) {
             $this->teamConn = $this->getConnection($this->teamDatabaseKey);
        }
        return new ScheduleFinder($this->gameConn,$this->poolConn,$this->teamConn);
    }
    public function testLoad()
    {
        // Just to create the connections
        $this->createScheduleFinder();

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($this->gameConn, $schemaFile);

        $projectKey = $this->projectKey;

        // Create some registered teams
        $programs = ['Core'];
        $genders  = ['B','G'];
        $ages     = ['U12','U14'];
        foreach($programs as $program) {
            foreach($ages as $age) {
                foreach($genders as $gender) {
                    $division = $age . $gender;
                    foreach([1,2,3,4,5,6] as $teamNumber) {
                        $teamKey = sprintf('%s%s#%02d',$division,$program,$teamNumber);
                        $teamId  = $projectKey . ':' . $teamKey;
                        $name    = sprintf('#%02d',$teamNumber);
                        $regTeam = [
                            'id'         => $teamId,
                            'projectKey' => $projectKey,
                            'teamKey'    => $teamKey,
                            'teamNumber' => $teamNumber,
                            'name'       => $name,
                            'status'     => 'Active',
                            'program'    => $program,
                            'gender'     => $gender,
                            'age'        => $age,
                            'division'   => $division,
                        ];
                        $this->teamConn->insert('projectTeams',$regTeam);
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

                            $poolTeamId = $projectKey . ':' . $poolTeamKey;

                            $teamNumber++;
                            $teamKey = sprintf('%s%s#%02d',$division,$program,$teamNumber);
                            $regTeamId  = $projectKey . ':' . $teamKey;

                            $poolTeam = [
                                'id'          => $poolTeamId,
                                'projectKey'  => $projectKey,

                                'poolKey'     => $poolKey,
                                'poolType'    => $poolType,
                                'poolTeamKey' => $poolTeamKey,

                                'poolView'     => $poolView,
                                'poolTypeView' => $poolType,
                                'poolTeamView' => $poolTeamView,
                                'poolTeamSlotView' => $poolName . $poolTeamNumber,

                                'program'    => $program,
                                'gender'     => $gender,
                                'age'        => $age,
                                'division'   => $division,

                                'projectTeamId' => $regTeamId,
                            ];
                            $this->teamConn->insert('projectPoolTeams',$poolTeam);
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
                            $this->initGame($projectKey, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away);
                        }
                    }
                }
            }
        }
    }
    /** Copied from the init program */
    private function initGame($projectKey, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away)
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

        $gameId = $projectKey . ':' . $gameNumber;
        $game = [
            'id'         => $gameId,
            'projectKey' => $projectKey,
            'gameNumber' => $gameNumber,
            'fieldName'  => $age . ' ' . $fieldNumber,
            'venueName'  => 'Rio',

            'start'  => $start,
            'finish' => $finishDateTime->format('Y-m-d H:i:s'),

            'state'       => 'Published',
            'status'      => 'Normal',
            'reportState' => 'Initial',
        ];
        $this->gameConn->insert('projectGames',$game);

        // Game officials are easy
        $isMedalRound = in_array(substr($home,0,2),['QF','SF','TF']);
        $gameOfficial = [
            'projectKey'  => $projectKey,
            'gameNumber'  => $gameNumber,
            'assignRole'  => $isMedalRound ? 'ROLE_ASSIGNOR' : 'ROLE_REFEREE',
            'assignState' => 'Open',
            'gameId'      => $gameId,
        ];
        foreach([1,2,3] as $slot) {
            $gameOfficial['id']   = $gameId . ':' . $slot;
            $gameOfficial['slot'] = $slot;
            $this->gameConn->insert('projectGameOfficials',$gameOfficial);
        }
        // Teams need a bit more work
        $gameTeam = [
            'gameId'      => $gameId,
            'projectKey'  => $projectKey,
            'gameNumber'  => $gameNumber,
            'name'        => null,
            'poolTeamId'  => null,
        ];
        foreach([1,2] as $slot)
        {
            $team = $slot === 1 ? $home : $away;

            $poolTeamName = $isMedalRound ? $team : 'PP' . $team;

            $poolTeamId = sprintf('%s:%s%s%s%s', $projectKey, $age, $gender, $program, $poolTeamName);

            $gameTeam['id']   = $gameId . ':' . $slot;
            $gameTeam['slot'] = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('projectGameTeams',$gameTeam);
        }
    }
    
    public function testFindRegTeams()
    {
        $finder = $this->createScheduleFinder();
        $criteria = [
            'projectKeys' => [$this->projectKey],
            'genders' => ['G'],
        ];
        $regTeams = $finder->findRegTeams($criteria);
        $this->assertCount(12,$regTeams);

        /** @var ScheduleRegTeam[] $regTeams */
        $regTeams = array_values($regTeams);
        $regTeam = $regTeams[3];
        $this->assertEquals('G',  $regTeam->gender);
        $this->assertEquals('#04',$regTeam->name);
    }
    public function testFindPoolTeams()
    {
        $finder = $this->createScheduleFinder();
        $criteria = [
            'projectKeys' => [$this->projectKey],
            'poolKeys'    => ['U14BCorePPA','U14BCorePPB'],
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
            'projectKeys' => [$this->projectKey],
            'divisions'   => ['U14B'],
        ];
        $games = $finder->findGames($criteria);
        $this->assertCount(6,$games);
        $game = $games[3];
        $this->assertInternalType('integer',$game->gameNumber);
        $this->assertEquals(16,$game->gameNumber);

        $homeTeam = $game->homeTeam;
        $this->assertEquals('U14B',$homeTeam->division);
        $this->assertEquals('#02', $homeTeam->name);
        $this->assertEquals('U14-B Core PP A',$homeTeam->poolView);
    }
}
