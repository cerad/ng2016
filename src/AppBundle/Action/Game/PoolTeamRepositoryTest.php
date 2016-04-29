<?php
namespace AppBundle\Action\Game;

use AppBundle\Common\DatabaseTrait;
use AppBundle\Common\DirectoryTrait;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;

use PHPUnit_Framework_TestCase;

class PoolTeamRepositoryTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    use DirectoryTrait;
    
    private $poolDatabaseKey = 'database_name_test';

    private $poolConn;

    private function createPoolTeamRepository()
    {
        if (!$this->poolConn) {
            $this->poolConn = $this->getConnection($this->poolDatabaseKey);
        }
        return new PoolTeamRepository($this->poolConn);
    }

    public function testSave()
    {
        $this->poolConn = $conn = $this->getConnection($this->poolDatabaseKey);

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($conn,$schemaFile);

        $repo = $this->createPoolTeamRepository();

        $projectKey = 'WorldCup2016';

        $poolTeams = [];

        foreach(['U12','U14'] as $age) {

            foreach(['B','G'] as $gender) {

                foreach(['A','B','C','D'] as $pool) {

                    foreach (['1', '2', '3', '4','5','6'] as $slot) {

                        $poolKey     = sprintf('%s%sPP%s',   $age, $gender, $pool);
                        $poolTeamKey = sprintf('%s%sPP%s%s', $age, $gender, $pool, $slot);

                        $poolTeam = new PoolTeam($projectKey, $poolTeamKey, $poolKey, 'PP');

                        $poolTeam->poolView     = sprintf('%s-%s PP %s',   $age, $gender, $pool);
                        $poolTeam->poolTeamView = sprintf('%s-%s PP %s%s', $age, $gender, $pool, $slot);
                        $poolTeam->poolTeamSlotView = $pool . $slot;

                        $poolTeam->program  = 'Core';
                        $poolTeam->gender   = $gender;
                        $poolTeam->age      = $age;
                        $poolTeam->division = $age . $gender;

                        $poolTeams[$slot] = $repo->save($poolTeam);
                    }
                }
            }
        }
    }
    public function testFind()
    {
        $repo = $this->createPoolTeamRepository();

        $poolTeamId = new PoolTeamId('WorldCup2016','U14GPPC3');

        $poolTeam = $repo->find($poolTeamId);

        $this->assertEquals('U14-G PP C3',$poolTeam->poolTeamView);
    }
    public function testFindBy()
    {
        $repo = $this->createPoolTeamRepository();

        $criteria = [
            'projectKeys' => ['WorldCup2016'],
        ];
        $poolTeams = $repo->findBy($criteria);
        $this->assertCount(96,$poolTeams);

        $criteria = [
            'projectKeys' => ['WorldCup2016'],
            'divisions'   => ['U14B'],
        ];
        $poolTeams = $repo->findBy($criteria);
        $this->assertCount(24,$poolTeams);

        $criteria = [
            'projectKeys' => ['WorldCup2016'],
            'genders'     => ['G'],
            'ages'        => ['U12','U14'],
        ];
        $poolTeams = $repo->findBy($criteria);
        $this->assertCount(48,$poolTeams);

        $criteria = [
            'projectKeys' => ['WorldCup2016'],
            'poolKeys'    => ['U14GPPC','U12BPPA'],
        ];
        $poolTeams = $repo->findBy($criteria);
        $this->assertCount(12,$poolTeams);

        $criteria = [
            'ids' => ['WorldCup2016:U14GPPB5','WorldCup2016:U14GPPB6'],
        ];
        $poolTeams = $repo->findBy($criteria);
        $this->assertCount(96,$poolTeams);

        $poolTeam = $poolTeams[1];

        $this->assertEquals('U12-B PP A', $poolTeam->poolView);
        $this->assertEquals('U12-B PP A2',$poolTeam->poolTeamView);
        $this->assertEquals('A2',         $poolTeam->poolTeamSlotView);

    }
}