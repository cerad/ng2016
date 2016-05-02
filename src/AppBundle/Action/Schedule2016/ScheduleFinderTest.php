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

        /** @var ScheduleTeam[] $regTeams */
        $regTeams = array_values($regTeams);
        $regTeam = $regTeams[3];
        $this->assertEquals('G',  $regTeam->gender);
        $this->assertEquals('#04',$regTeam->name);
    }
}
