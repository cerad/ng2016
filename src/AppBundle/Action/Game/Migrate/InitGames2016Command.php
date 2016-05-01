<?php
namespace AppBundle\Action\Game\Migrate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraints\DateTime;

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

        $this->assignProjectTeamsToPoolPlayTeams($commit);

        $this->initGames(true);

        echo sprintf("Init Games NG2016 Completed.\n");
    }

    private $projectKey = 'AYSONationalGames2016';
    private $programs = ['Core'];
    private $genders = ['B', 'G'];
    private $ages = ['U10', 'U12', 'U14', 'U16', 'U19'];

    private function initProjectTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $this->projectTeamConn->delete('projectTeams', ['projectKey' => $this->projectKey]);

        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {
                    for ($teamNumber = 1; $teamNumber <= 24; $teamNumber++) {
                        $this->initProjectTeam($this->projectKey, $program, $age, $gender, $teamNumber);
                        $count++;
                        if (($count % 100) === 0) {
                            echo sprintf("\rLoading Project Teams %5d", $count);
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Project Teams %5d      \n", $count);
    }

    private function initProjectTeam($projectKey, $program, $age, $gender, $teamNumber)
    {
        $division = $age . $gender;

        $teamKey = sprintf('%s-%s-%02d', $division, $program, $teamNumber);

        $projectTeamId = $projectKey . ':' . $teamKey;

        $item = [
            'id'         => $projectTeamId,
            'projectKey' => $projectKey,
            'teamKey'    => $teamKey,
            'teamNumber' => $teamNumber,

            'name'   => sprintf('#%02d', $teamNumber),
            'status' => 'Active',

            'program'  => $program,
            'gender'   => $gender,
            'age'      => $age,
            'division' => $division,
        ];
        $this->projectTeamConn->insert('projectTeams', $item);
    }

    private function initPoolTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectKey = $this->projectKey;
        $this->poolConn->delete('projectPoolTeams', ['projectKey' => $projectKey]);
        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {
                    foreach (['A', 'B', 'C', 'D'] as $poolName) {
                        foreach ([1, 2, 3, 4, 5, 6] as $poolTeamName) {
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
                            '1' => [['game' =>  1, 'name' => 'X', 'slot' => 'A 1st'], ['name' => 'Y', 'slot' => 'C 2nd']],
                            '2' => [['game' =>  2, 'name' => 'X', 'slot' => 'B 1st'], ['name' => 'Y', 'slot' => 'D 2nd']],
                            '3' => [['game' =>  3, 'name' => 'X', 'slot' => 'C 1st'], ['name' => 'Y', 'slot' => 'A 2nd']],
                            '4' => [['game' =>  4, 'name' => 'X', 'slot' => 'D 1st'], ['name' => 'Y', 'slot' => 'B 2nd']],
                        ],
                        'SF' => [
                            '1' => [['game' =>  5, 'name' => 'X', 'slot' => 'QF1 Win'], ['name' => 'Y', 'slot' => 'QF2 Win']],
                            '2' => [['game' =>  6, 'name' => 'X', 'slot' => 'QF3 Win'], ['name' => 'Y', 'slot' => 'QF4 Win']],
                            '3' => [['game' =>  7, 'name' => 'X', 'slot' => 'QF1 Los'], ['name' => 'Y', 'slot' => 'QF2 Los']],
                            '4' => [['game' =>  8, 'name' => 'X', 'slot' => 'QF3 Los'], ['name' => 'Y', 'slot' => 'QF4 Los']],
                        ],
                        'TF' => [
                            '1' => [['game' =>  9, 'name' => 'X', 'slot' => 'SF1 Win'], ['name' => 'Y', 'slot' => 'SF2 Win']],
                            '2' => [['game' => 10, 'name' => 'X', 'slot' => 'SF1 Los'], ['name' => 'Y', 'slot' => 'SF2 Los']],
                            '3' => [['game' => 11, 'name' => 'X', 'slot' => 'SF3 Win'], ['name' => 'Y', 'slot' => 'SF4 Win']],
                            '4' => [['game' => 12, 'name' => 'X', 'slot' => 'SF3 Los'], ['name' => 'Y', 'slot' => 'SF4 Los']],
                        ],
                        'ZZ' => [ // Two teams is probably enough, could add 12 teams per pool, decide later
                            '01-12' => [['name' => 'X', 'slot' => 'Team 1',], ['name' => 'Y', 'slot' => 'Team 2']],
                            '13-24' => [['name' => 'X', 'slot' => 'Team 1',], ['name' => 'Y', 'slot' => 'Team 2']],
                        ],
                    ];
                    foreach ($medalRoundPools as $poolType => $pools) {
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
        echo sprintf("\rLoaded Pool Teams %5d      \n", $count);
    }

    private function initPoolTeam($projectKey, $poolType, $poolName, $poolTeamName, $poolSlot, $program, $age, $gender)
    {
        $division = $age . $gender;

        $poolTypeView = $poolType;

        switch ($poolType) {
            case 'TF':
                $poolTypeView = 'FM';
                break;
            case 'ZZ':
                $poolTypeView = 'SOF';
                break;
        }
        $poolView = sprintf('%s-%s %s %s %s', $age, $gender, $program, $poolTypeView, $poolName);
        $poolTeamView = sprintf('%s-%s %s %s %s', $age, $gender, $program, $poolTypeView, $poolSlot);

        $poolKey = sprintf('%s%s%s%s', $division, $program, $poolType, $poolName);
        $poolTeamKey = sprintf('%s%s%s%s%s', $division, $program, $poolType, $poolName, $poolTeamName);

        $poolTeamId = $projectKey . ':' . $poolTeamKey;

        $item = [
            'id' => $poolTeamId,

            'projectKey' => $projectKey,

            'poolType' => $poolType,
            'poolKey' => $poolKey,
            'poolTeamKey' => $poolTeamKey,

            'poolView' => $poolView,
            'poolTypeView' => $poolTypeView,
            'poolTeamView' => $poolTeamView,
            'poolTeamSlotView' => $poolSlot,

            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,

            'projectTeamId' => null,
        ];

        $this->poolConn->insert('projectPoolTeams', $item);
    }

    private function assignProjectTeamsToPoolPlayTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectKey = $this->projectKey;

        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {

                    // Fetch the project teams
                    $sql = 'SELECT id,orgKey FROM projectTeams WHERE projectKey = ? AND program = ? AND age = ? AND gender = ?';
                    $stmt = $this->projectTeamConn->executeQuery($sql, [$projectKey, $program, $age, $gender]);
                    $projectTeams = $stmt->fetchAll();

                    // Fetch the pool teams
                    $sql = 'SELECT id FROM projectPoolTeams WHERE projectKey = ? AND program = ? AND age = ? AND gender = ? AND poolType = \'PP\'';
                    $stmt = $this->poolConn->executeQuery($sql, [$projectKey, $program, $age, $gender]);
                    $poolTeams = $stmt->fetchAll();

                    if (count($projectTeams) !== count($poolTeams)) {
                        die('ProjectTeam PoolTeam count mismatch');
                    }
                    $teamCount = count($projectTeams);
                    foreach ($projectTeams as $projectTeam) {
                        $projectTeamId = $projectTeam['id'];
                        $tryAgain = true;
                        while ($tryAgain) {
                            $random = rand(0, $teamCount - 1);
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
        echo sprintf("\rAssigned Pool Play Teams %5d      \n", $count);
    }

    private function initGames($commit)
    {
        if (!$commit) {
            return;
        }
        /* $timeSlots = [
            'Wed' => ['08:00:00','09:15:00','10:30:00','11:45:00','13:00:00','14:15:00','15:30:00','16:45:00'], // Soccerfest
            'Thu' => ['08:00:00','09:15:00','10:30:00','11:45:00','13:00:00','14:15:00','15:30:00','16:45:00'], // Pool Play
            'Fri' => ['08:00:00','09:15:00','10:30:00','11:45:00','13:00:00','14:15:00','15:30:00','16:45:00'], // Pool Play
            'Sat' => ['08:00:00','09:15:00','10:30:00','11:45:00','13:00:00','15:00:00'],                       // Pool Play / Quarter Finals
            'Sun' => ['08:00:00','10:00:00','12:00:00','14:00:00'],                                             // Semi Fields / Finals
        ]; */
        // Six fields per division
        $fieldSlots = [
            [1, 'Thu', '08:00', 'A1', 'A2', 'G'], [2, 'Thu', '08:00', 'A3', 'A5', 'G'], [3, 'Thu', '08:00', 'A4', 'A6', 'G'],
            [4, 'Thu', '08:00', 'C1', 'C2', 'G'], [5, 'Thu', '08:00', 'C3', 'C5', 'G'], [6, 'Thu', '08:00', 'C4', 'C6', 'G'],
            [1, 'Thu', '09:15', 'D1', 'D2', 'G'], [2, 'Thu', '09:15', 'D3', 'D5', 'G'], [3, 'Thu', '09:15', 'D4', 'D6', 'G'],
            [4, 'Thu', '09:15', 'B1', 'B2', 'G'], [5, 'Thu', '09:15', 'B3', 'B5', 'G'], [6, 'Thu', '09:15', 'B4', 'B6', 'G'],

            [1, 'Thu', '10:30', 'A1', 'A2', 'B'], [2, 'Thu', '10:30', 'A3', 'A5', 'B'], [3, 'Thu', '10:30', 'A4', 'A6', 'B'],
            [4, 'Thu', '10:30', 'C1', 'C2', 'B'], [5, 'Thu', '10:30', 'C3', 'C5', 'B'], [6, 'Thu', '10:30', 'C4', 'C6', 'B'],
            [1, 'Thu', '11:45', 'D1', 'D2', 'B'], [2, 'Thu', '11:45', 'D3', 'D5', 'B'], [3, 'Thu', '11:45', 'D4', 'D6', 'B'],
            [4, 'Thu', '11:45', 'B1', 'B2', 'B'], [5, 'Thu', '11:45', 'B3', 'B5', 'B'], [6, 'Thu', '11:45', 'B4', 'B6', 'B'],

            [1, 'Thu', '13:00', 'A2', 'A4', 'G'], [2, 'Thu', '13:00', 'A3', 'A1', 'G'], [3, 'Thu', '13:00', 'A6', 'A5', 'G'],
            [4, 'Thu', '13:00', 'C2', 'C4', 'G'], [5, 'Thu', '13:00', 'C3', 'C1', 'G'], [6, 'Thu', '13:00', 'C6', 'C5', 'G'],
            [1, 'Thu', '14:15', 'D2', 'D4', 'G'], [2, 'Thu', '14:15', 'D3', 'D1', 'G'], [3, 'Thu', '14:15', 'D6', 'D5', 'G'],
            [4, 'Thu', '14:15', 'B2', 'B4', 'G'], [5, 'Thu', '14:15', 'B3', 'B1', 'G'], [6, 'Thu', '14:15', 'B6', 'B5', 'G'],

            [1, 'Thu', '15:30', 'A2', 'A4', 'B'], [2, 'Thu', '15:30', 'A3', 'A1', 'B'], [3, 'Thu', '15:30', 'A6', 'A5', 'B'],
            [4, 'Thu', '15:30', 'C2', 'C4', 'B'], [5, 'Thu', '15:30', 'C3', 'C1', 'B'], [6, 'Thu', '15:30', 'C6', 'C5', 'B'],
            [1, 'Thu', '16:45', 'D2', 'D4', 'B'], [2, 'Thu', '16:45', 'D3', 'D1', 'B'], [3, 'Thu', '16:45', 'D6', 'D5', 'B'],
            [4, 'Thu', '16:45', 'B2', 'B4', 'B'], [5, 'Thu', '16:45', 'B3', 'B1', 'B'], [6, 'Thu', '16:45', 'B6', 'B5', 'B'],

            [1, 'Fri', '08:00', 'A5', 'A2', 'B'], [2, 'Fri', '08:00', 'A1', 'A4', 'B'], [3, 'Fri', '08:00', 'A6', 'A3', 'B'],
            [4, 'Fri', '08:00', 'C5', 'C2', 'B'], [5, 'Fri', '08:00', 'C1', 'C4', 'B'], [6, 'Fri', '08:00', 'C6', 'C3', 'B'],
            [1, 'Fri', '09:15', 'D5', 'D2', 'B'], [2, 'Fri', '09:15', 'D1', 'D4', 'B'], [3, 'Fri', '09:15', 'A6', 'A3', 'B'],
            [4, 'Fri', '09:15', 'B5', 'B2', 'B'], [5, 'Fri', '09:15', 'B1', 'B4', 'B'], [6, 'Fri', '09:15', 'C6', 'C3', 'B'],

            [1, 'Fri', '10:30', 'A5', 'A2', 'G'], [2, 'Fri', '10:30', 'A1', 'A4', 'G'], [3, 'Fri', '10:30', 'A6', 'A3', 'G'],
            [4, 'Fri', '10:30', 'C5', 'C2', 'G'], [5, 'Fri', '10:30', 'C1', 'C4', 'G'], [6, 'Fri', '10:30', 'C6', 'C3', 'G'],
            [1, 'Fri', '11:45', 'D5', 'D2', 'G'], [2, 'Fri', '11:45', 'D1', 'D4', 'G'], [3, 'Fri', '11:45', 'A6', 'A3', 'G'],
            [4, 'Fri', '11:45', 'B5', 'B2', 'G'], [5, 'Fri', '11:45', 'B1', 'B4', 'G'], [6, 'Fri', '11:45', 'C6', 'C3', 'G'],

            [1, 'Fri', '13:00', 'A2', 'A6', 'B'], [2, 'Fri', '13:00', 'A5', 'A1', 'B'], [3, 'Fri', '13:00', 'A4', 'A3', 'B'],
            [4, 'Fri', '13:00', 'C2', 'C6', 'B'], [5, 'Fri', '13:00', 'C5', 'C1', 'B'], [6, 'Fri', '13:00', 'C4', 'C3', 'B'],
            [1, 'Fri', '14:15', 'D2', 'D6', 'B'], [2, 'Fri', '14:15', 'D5', 'D1', 'B'], [3, 'Fri', '14:15', 'D4', 'D3', 'B'],
            [4, 'Fri', '14:15', 'B2', 'B6', 'B'], [5, 'Fri', '14:15', 'B5', 'B1', 'B'], [6, 'Fri', '14:15', 'B4', 'B3', 'B'],

            [1, 'Fri', '15:30', 'A2', 'A6', 'G'], [2, 'Fri', '15:30', 'A5', 'A1', 'G'], [3, 'Fri', '15:30', 'A4', 'A3', 'G'],
            [4, 'Fri', '15:30', 'C2', 'C6', 'G'], [5, 'Fri', '15:30', 'C5', 'C1', 'G'], [6, 'Fri', '15:30', 'C4', 'C3', 'G'],
            [1, 'Fri', '16:45', 'D2', 'D6', 'G'], [2, 'Fri', '16:45', 'D5', 'D1', 'G'], [3, 'Fri', '16:45', 'D4', 'D3', 'G'],
            [4, 'Fri', '16:45', 'B2', 'B6', 'G'], [5, 'Fri', '16:45', 'B5', 'B1', 'G'], [6, 'Fri', '16:45', 'B4', 'B3', 'G'],

            [1, 'Sat', '08:00', 'A3', 'A2', 'G'], [2, 'Sat', '08:00', 'A1', 'A6', 'G'], [3, 'Sat', '08:00', 'A4', 'A5', 'G'],
            [4, 'Sat', '08:00', 'C3', 'C2', 'G'], [5, 'Sat', '08:00', 'C1', 'C6', 'G'], [6, 'Sat', '08:00', 'C4', 'C5', 'G'],
            [1, 'Sat', '09:15', 'D3', 'D2', 'G'], [2, 'Sat', '09:15', 'D1', 'A6', 'G'], [3, 'Sat', '09:15', 'D4', 'D5', 'G'],
            [4, 'Sat', '09:15', 'B3', 'B2', 'G'], [5, 'Sat', '09:15', 'D1', 'C6', 'G'], [6, 'Sat', '09:15', 'B4', 'B5', 'G'],

            [1, 'Sat', '10:30', 'A3', 'A2', 'B'], [2, 'Sat', '10:30', 'A1', 'A6', 'B'], [3, 'Sat', '10:30', 'A4', 'A5', 'B'],
            [4, 'Sat', '10:30', 'C3', 'C2', 'B'], [5, 'Sat', '10:30', 'C1', 'C6', 'B'], [6, 'Sat', '10:30', 'C4', 'C5', 'B'],
            [1, 'Sat', '11:45', 'D3', 'D2', 'B'], [2, 'Sat', '11:45', 'D1', 'A6', 'B'], [3, 'Sat', '11:45', 'D4', 'D5', 'B'],
            [4, 'Sat', '11:45', 'B3', 'B2', 'B'], [5, 'Sat', '11:45', 'D1', 'C6', 'B'], [6, 'Sat', '11:45', 'B4', 'B5', 'B'],

            [3, 'Sat', '13:00', 'QF1X', 'QF1Y', 'G'],
            [4, 'Sat', '13:00', 'QF2X', 'QF2Y', 'G'],
            [5, 'Sat', '13:00', 'QF3X', 'QF3Y', 'G'],
            [6, 'Sat', '13:00', 'QF4X', 'QF4Y', 'G'],

            [3, 'Sat', '15:00', 'QF1X', 'QF1Y', 'B'],
            [4, 'Sat', '15:00', 'QF2X', 'QF2Y', 'B'],
            [5, 'Sat', '15:00', 'QF3X', 'QF3Y', 'B'],
            [6, 'Sat', '15:00', 'QF4X', 'QF4Y', 'B'],

            [1, 'Sun', '08:00', 'SF1X', 'SF1Y', 'B'],
            [2, 'Sun', '08:00', 'SF2X', 'SF2Y', 'B'],
            [3, 'Sun', '08:00', 'SF3X', 'SF3Y', 'B'],
            [4, 'Sun', '08:00', 'SF4X', 'SF4Y', 'B'],

            [3, 'Sun', '10:00', 'SF1X', 'SF1Y', 'G'],
            [4, 'Sun', '10:00', 'SF2X', 'SF2Y', 'G'],
            [5, 'Sun', '10:00', 'SF3X', 'SF3Y', 'G'],
            [6, 'Sun', '10:00', 'SF4X', 'SF4Y', 'G'],

            [1, 'Sun', '13:00', 'TF1X', 'TF1Y', 'B'],
            [2, 'Sun', '13:00', 'TF2X', 'TF2Y', 'B'],
            [3, 'Sun', '13:00', 'TF3X', 'TF3Y', 'B'],
            [4, 'Sun', '13:00', 'TF4X', 'TF4Y', 'B'],

            [3, 'Sun', '15:00', 'TF1X', 'TF1Y', 'G'],
            [4, 'Sun', '15:00', 'TF2X', 'TF2Y', 'G'],
            [5, 'Sun', '15:00', 'TF3X', 'TF3Y', 'G'],
            [6, 'Sun', '15:00', 'TF4X', 'TF4Y', 'G'],
        ];
        $projectKey = $this->projectKey;
        $this->gameConn->delete('projectGames',         ['projectKey' => $projectKey]);
        $this->gameConn->delete('projectGameTeams',     ['projectKey' => $projectKey]);
        $this->gameConn->delete('projectGameOfficials', ['projectKey' => $projectKey]);

        $count = 0;
        foreach ($this->programs as $program) {
            $gameNumberProgram = 0;
            switch ($program) {
                case 'Core':
                    $gameNumberProgram = 10000;
                    break;
            }
            foreach ($this->ages as $age) {
                $fieldNumberStart = substr($age,1) * 10;
                foreach ($this->genders as $gender) {

                    $gameNumber = $gameNumberProgram + (substr($age, 1) * 100);
                    if ($gender === 'G') {
                        $gameNumber += 2000;
                    }
                    foreach ($fieldSlots as $fieldSlot) {
                        list($fieldNumber, $dow, $time, $home, $away, $fieldSlotGender) = $fieldSlot;
                        if ($fieldSlotGender === $gender) {
                            $fieldNumber = $fieldNumberStart + $fieldNumber;
                            $gameNumber++;
                            $this->initGame($projectKey, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away);

                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\rLoading Games %5d", $count);
                            }
                            //if ($age === 'U19') {
                            //    echo sprintf("Game Number %d %s%s\n",$gameNumber,$age,$gender);
                            //}
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Games %5d      \n",$count);
    }
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
            'venueName'  => 'Polo',

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

            $sql = 'SELECT projectTeamId FROM projectPoolTeams WHERE id = ?';
            $stmt = $this->poolConn->executeQuery($sql,[$poolTeamId]);
            $row = $stmt->fetch();
            if (!$row) {
                echo sprintf("No Pool Team For: %s\n",$poolTeamId);
                die();
            }
            $projectTeamId = $row['projectTeamId'];
            if ($projectTeamId) {
                $sql = 'SELECT name FROM projectTeams WHERE id = ?';
                $stmt = $this->poolConn->executeQuery($sql,[$projectTeamId]);
                $row = $stmt->fetch();
                if (!$row) {
                    echo sprintf("No Project Team For: %s\n",$projectTeamId);
                    die();
                }
                $gameTeam['name'] = $row['name'];
            }
            $gameTeam['id']   = $gameId . ':' . $slot;
            $gameTeam['slot'] = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('projectGameTeams',$gameTeam);
        }
    }
}