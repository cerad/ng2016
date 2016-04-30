<?php
namespace AppBundle\Action\Game\Migrate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class InitGames2016Command extends Command
{
    private $gameConn;
    private $poolConn;
    private $projectTeamConn;

    public function __construct(Connection $ng2016GamesConn)
    {
        parent::__construct();
        
        $this->gameConn = $ng2016GamesConn;
        $this->poolConn = $ng2016GamesConn;
        $this->projectTeamConn = $ng2016GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('init:games:ng2016')
            ->setDescription('Init Games NG2016');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Init Games NG2016 ...\n");

        $commit = false;

        $this->initProjectTeams($commit);

        $this->initPoolTeams($commit);

        $this->assignProjectTeamsToPoolPlayTeams(true);

        echo sprintf("Init Games NG2016 Completed.\n");
    }
    private $projectKey = 'AYSONationalGames2016';
    private $programs   = ['Core'];
    private $genders    = ['B','G'];
    private $ages       = ['U10','U12','U14','U16','U19'];

    private function initProjectTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $this->projectTeamConn->delete('projectTeams',['projectKey' => $this->projectKey]);

        foreach($this->programs as $program) {
            foreach($this->ages as $age) {
                foreach($this->genders as $gender) {
                    for($teamNumber = 1; $teamNumber <= 24; $teamNumber++) {
                        $this->initProjectTeam($this->projectKey,$program,$age,$gender,$teamNumber);
                        $count++;
                        if (($count % 100) === 0) {
                            echo sprintf("\rLoading Project Teams %5d",$count);
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Project Teams %5d      \n",$count);
    }
    private function initProjectTeam($projectKey,$program,$age,$gender,$teamNumber)
    {
        $division = $age . $gender;

        $teamKey = sprintf('%s-%s-%02d',$division,$program,$teamNumber);

        $projectTeamId = $projectKey . ':' . $teamKey;

        $item = [
            'id'         => $projectTeamId,
            'projectKey' => $projectKey,
            'teamKey'    => $teamKey,
            'teamNumber' => $teamNumber,

            'name'   => sprintf('#%02d ',$teamNumber),
            'status' => 'Active',

            'program'  => $program,
            'gender'   => $gender,
            'age'      => $age,
            'division' => $division,
        ];
        $this->projectTeamConn->insert('projectTeams',$item);
    }
    private function initPoolTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectKey = $this->projectKey;
        $this->poolConn->delete('projectPoolTeams',['projectKey' => $projectKey]);
        foreach($this->programs as $program) {
            foreach($this->ages as $age) {
                foreach($this->genders as $gender) {
                    foreach(['A','B','C','D'] as $poolName) {
                        foreach([1,2,3,4,5,6] as $poolTeamName) {
                            $poolTeamSlot = $poolName . $poolTeamName;
                            $this->initPoolTeam($projectKey, 'PP', $poolName, $poolTeamName, $poolTeamSlot, $program, $age, $gender);
                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\rLoading Pool Teams %5d", $count);
                            }
                        }
                    }
                    $medalRoundPools = [
                        'QF' => [
                            '1' => [[ 'game' =>  1, 'name' => 'X', 'slot' => 'A 1st', ], [ 'name' => 'Y', 'slot' => 'C 2nd' ]],
                            '2' => [[ 'game' =>  2, 'name' => 'X', 'slot' => 'B 1st', ], [ 'name' => 'Y', 'slot' => 'D 2nd' ]],
                            '3' => [[ 'game' =>  3, 'name' => 'X', 'slot' => 'C 1st', ], [ 'name' => 'Y', 'slot' => 'A 2nd' ]],
                            '4' => [[ 'game' =>  4, 'name' => 'X', 'slot' => 'D 1st', ], [ 'name' => 'Y', 'slot' => 'B 2nd' ]],
                        ],
                        'SF' => [
                            '1' => [[ 'game' =>  5, 'name' => 'X', 'slot' => 'QF1 Win', ], [ 'name' => 'Y', 'slot' => 'QF2 Win' ]],
                            '2' => [[ 'game' =>  6, 'name' => 'X', 'slot' => 'QF3 Win', ], [ 'name' => 'Y', 'slot' => 'QF4 Win' ]],
                            '3' => [[ 'game' =>  7, 'name' => 'X', 'slot' => 'QF1 Los', ], [ 'name' => 'Y', 'slot' => 'QF2 Los' ]],
                            '4' => [[ 'game' =>  8, 'name' => 'X', 'slot' => 'QF3 Los', ], [ 'name' => 'Y', 'slot' => 'QF4 Los' ]],
                        ],
                        'TF' => [
                            '1' => [[ 'game' =>  9, 'name' => 'X', 'slot' => 'SF1 Win', ], [ 'name' => 'Y', 'slot' => 'SF2 Win' ]],
                            '2' => [[ 'game' => 10, 'name' => 'X', 'slot' => 'SF1 Los', ], [ 'name' => 'Y', 'slot' => 'SF2 Los' ]],
                            '3' => [[ 'game' => 11, 'name' => 'X', 'slot' => 'SF3 Win', ], [ 'name' => 'Y', 'slot' => 'SF4 Win' ]],
                            '4' => [[ 'game' => 12, 'name' => 'X', 'slot' => 'SF3 Los', ], [ 'name' => 'Y', 'slot' => 'SF4 Los' ]],
                        ],
                        'ZZ' => [ // Two teams is probably enough, could add 12 teams per pool, decide later
                            '01-12' => [[ 'name' => 'X', 'slot' => 'Team 1', ], [ 'name' => 'Y', 'slot' => 'Team 2' ]],
                            '13-24' => [[ 'name' => 'X', 'slot' => 'Team 1', ], [ 'name' => 'Y', 'slot' => 'Team 2' ]],
                        ],
                    ];
                    foreach($medalRoundPools as $poolType => $pools) {
                        foreach ($pools as $poolName => $poolTeams) {
                            foreach ($poolTeams as $poolTeam) {
                                $this->initPoolTeam($projectKey, $poolType, $poolName, $poolTeam['name'], $poolTeam['slot'], $program, $age, $gender);
                                $count++;
                                if (($count % 100) === 0) {
                                    echo sprintf("\rLoading Pool Teams %5d", $count);
                                }
                            }
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Pool Teams %5d      \n",$count);
    }
    private function initPoolTeam($projectKey,$poolType,$poolName,$poolTeamName,$poolSlot,$program,$age,$gender)
    {
        $division = $age . $gender;

        $poolTypeView = $poolType;

        switch($poolType) {
            case 'TF': $poolTypeView = 'FM';  break;
            case 'ZZ': $poolTypeView = 'SOF'; break;
        }
        $poolView     = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolTypeView,$poolName);
        $poolTeamView = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolTypeView,$poolSlot);

        $poolKey     = sprintf('%s%s%s%s',  $division,$program,$poolType,$poolName);
        $poolTeamKey = sprintf('%s%s%s%s%s',$division,$program,$poolType,$poolName,$poolTeamName);

        $poolTeamId = $projectKey . ':' . $poolTeamKey;

        $item = [
            'id' => $poolTeamId,

            'projectKey'   => $projectKey,

            'poolType'     => $poolType,
            'poolKey'      => $poolKey,
            'poolTeamKey'  => $poolTeamKey,

            'poolView'         => $poolView,
            'poolTypeView'     => $poolTypeView,
            'poolTeamView'     => $poolTeamView,
            'poolTeamSlotView' => $poolSlot,

            'program'  => $program,
            'gender'   => $gender,
            'age'      => $age,
            'division' => $division,

            'projectTeamId' => null,
        ];

       $this->poolConn->insert('projectPoolTeams',$item);
    }

    private function assignProjectTeamsToPoolPlayTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectKey = $this->projectKey;

        foreach($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {

                    // Fetch the project teams
                    $sql = 'SELECT id,orgKey FROM projectTeams WHERE projectKey = ? AND program = ? AND age = ? AND gender = ?';
                    $stmt = $this->projectTeamConn->executeQuery($sql,[$projectKey,$program,$age,$gender]);
                    $projectTeams = $stmt->fetchAll();

                    // Fetch the pool teams
                    $sql = 'SELECT id FROM projectPoolTeams WHERE projectKey = ? AND program = ? AND age = ? AND gender = ? AND poolType = \'PP\'';
                    $stmt = $this->poolConn->executeQuery($sql,[$projectKey,$program,$age,$gender]);
                    $poolTeams = $stmt->fetchAll();

                    if (count($projectTeams) !== count($poolTeams)) {
                        die('ProjectTeam PoolTeam count mismatch');
                    }
                    $teamCount = count($projectTeams);
                    foreach($projectTeams as $projectTeam) {
                        $projectTeamId = $projectTeam['id'];
                        $tryAgain = true;
                        while($tryAgain) {
                            $random = rand(0,$teamCount-1);
                            if (!isset($poolTeams[$random]['projectTeamId'])) {

                                $this->poolConn->update('projectPoolTeams',
                                    ['projectTeamId' => $projectTeamId],
                                    ['id' => $poolTeams[$random]['id']]
                                );
                                $poolTeams[$random]['projectTeamId'] = $projectTeamId;
                                $tryAgain = false;

                                $count++;
                                if (($count % 100) === 0) {
                                    echo sprintf("\rAssigning Pool Play Teams %5d", $count);
                                }
                            }
                        }
                    }
                }
            }
        }
        echo sprintf("\rAssigned Pool Play Teams %5d      \n",$count);
    }
}