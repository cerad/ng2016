<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class InitGames2018Command extends Command
{
    private $gameConn;
    private $regTeamConn;

    public function __construct(Connection $conn)
    {
        parent::__construct();

        $this->gameConn = $conn;
        $this->regTeamConn = $conn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:init:games')
            ->setDescription('Init Games NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Init Games NOC2018 ...\n");

        $commit = false;

        $this->initRegTeams($commit || true);

        $this->initPoolTeams($commit || true);

        $this->assignRegTeamsToPoolPlayTeams($commit || true);

        $this->initGames($commit || true);

        echo sprintf("Init Games NOC2018 Completed.\n");
    }

    private $projectId = 'AYSONationalOpenCup2018';
    private $programs = ['Core', 'Extra', 'Club', 'VIP'];
    private $genders = ['B', 'G'];
    private $ages = [
        'Core' => ['10U', '12U', '14U', '16U', '19U'],
        'Extra' => ['10U', '11U', '12U', '13U', '14U'],
        'Club' => ['2008', '2007', '2006', '2005', '2004', '2003', '2002'],
        'VIP' => ['VIP'],
    ];

    private function getPools($division)
    {
        switch ($division) {
            case 'B14U':
                return ['A'];
        }

        return ['A'];
    }

    private function initRegTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $this->regTeamConn->delete('regTeams', ['projectId' => $this->projectId]);

        $count = 0;
        foreach ($this->programs as $program) {
            foreach ($this->ages[$program] as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($gender.$age);
                    $teamCount = count($pools) * 6;
                    for ($teamNumber = 1; $teamNumber <= $teamCount; $teamNumber++) {
                        $this->initRegTeam($this->projectId, $program, $age, $gender, $teamNumber);
                        $count++;
                        if (($count % 100) === 0) {
                            echo sprintf("\rLoading Registration Teams %5d", $count);
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Registration Teams %5d      \n", $count);
    }

    private function initRegTeam(
        $projectId,
        $program,
        $age,
        $gender,
        $teamNumber
    ) {
        $division = $gender.$age;

        $teamKey = sprintf('%s%s%02d', $division, $program, $teamNumber);

        $regTeamId = $projectId.':'.$teamKey;

        $regTeam = [
            'regTeamId' => $regTeamId,
            'projectId' => $projectId,
            'teamKey' => $teamKey,
            'teamNumber' => $teamNumber,
            'teamName' => sprintf('#%02d', $teamNumber),
            'teamPoints' => null,

            'orgId' => null,
            'orgView' => null,

            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,
        ];
        $this->regTeamConn->insert('regTeams', $regTeam);
    }

    private function initPoolTeams(
        $commit
    ) {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectId = $this->projectId;
        $this->gameConn->delete('poolTeams', ['projectId' => $projectId]);
        foreach ($this->programs as $program) {
            foreach ($this->ages[$program] as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($gender.$age);
                    foreach ($pools as $poolName) {
                        foreach ([1, 2, 3, 4, 5, 6] as $poolTeamName) {
                            $poolTeamSlot = $poolName.$poolTeamName;
                            $this->initPoolTeam(
                                $projectId,
                                'PP',
                                $poolName,
                                $poolTeamName,
                                $poolTeamSlot,
                                $program,
                                $age,
                                $gender
                            );
                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\rLoading Pool Teams %5d", $count);
                            }
                        }
                    }
                    $medalRoundPools = [
                        'SF' => [
                            '1' => [
                                ['game' => 5, 'name' => 'X', 'slot' => 'A 1st'],
                                ['game' => 5, 'name' => 'Y', 'slot' => 'B 2nd'],
                            ],
                            '2' => [
                                ['game' => 6, 'name' => 'X', 'slot' => 'B 1st'],
                                ['game' => 6, 'name' => 'Y', 'slot' => 'A 2nd'],
                            ],
                        ],
                        'TF' => [
                            '1' => [
                                ['game' => 7, 'name' => 'X', 'slot' => 'A 1st'],
                                ['game' => 7, 'name' => 'Y', 'slot' => 'A 2nd'],
                            ],
                        ],
                    ];
                    foreach ($medalRoundPools as $poolType => $rounds) {
                        foreach ($rounds as $poolName => $poolTeams) {
                            foreach ($poolTeams as $poolTeam) {
                                if ($poolType === 'QF' && count($pools) < 4) {
                                    $poolTeam['slot'] = 'TBD';
                                }
                                // Real hack here
                                $game = isset($poolTeam['game']) ? $poolTeam['game'] : null;
                                $this->initPoolTeam(
                                    $projectId,
                                    $poolType,
                                    $poolName,
                                    $poolTeam['name'],
                                    $poolTeam['slot'],
                                    $program,
                                    $age,
                                    $gender,
                                    $game
                                );
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

    private
    function initPoolTeam(
        $projectId,
        $poolType,
        $poolName,
        $poolTeamName,
        $poolSlot,
        $program,
        $age,
        $gender,
        $game = null
    ) {
        $division = $gender.$age;

        $poolTypeView = $poolType;

        switch ($poolType) {
            case 'TF':
                $poolTypeView = 'FM';
                break;
            case 'ZZ':
                $poolTypeView = 'SOF';
                break;
        }
        $poolNameView = $game ? sprintf('%2s', $game) : $poolName;
        switch ($poolType) {
            case 'PP':
                $poolTypeDesc = 'Pool Play';
                break;
            case 'QF':
                $poolTypeDesc = 'Quarter-Final';
                break;
            case 'SF':
                $poolTypeDesc = 'Semi-Final';
                break;
            case 'TF':
                $poolTypeDesc = 'Final';
                break;

            case 'ZZ':
                $poolTypeDesc = 'Soccerfest';
                break;
            default:
                $poolTypeDesc = 'UNKNOWN POOL DESC';
        }
        $poolView = sprintf('%s%s %s %s', $gender, $age, $poolTypeDesc, $poolNameView);
        $poolTeamView = sprintf('%s%s %s %s', $gender, $age, $poolTypeDesc, $poolSlot);

        switch ($game) {
            case 5:
            case 6:
            case 7:
            case 8:
                $bracket = 'Championship';
                break;
            case 9:
            case 10:
            case 11:
            case 12:
                $bracket = 'Consolation';
                break;
            default:
                $bracket = null;
        }
        // Append bracket, probably should use new line here, adjust view later
        $poolView = $bracket ? $poolView.'<br>'.$bracket : $poolView;

        $poolKey = sprintf('%s%s%s%s', $division, $program, $poolType, $poolName);
        $poolTeamKey = sprintf('%s%s%s%s%s', $division, $program, $poolType, $poolName, $poolTeamName);

        $poolTeamId = $projectId.':'.$poolTeamKey;

        $poolTeam = [
            'poolTeamId' => $poolTeamId,
            'projectId' => $projectId,

            'poolKey' => $poolKey,
            'poolTypeKey' => $poolType,
            'poolTeamKey' => $poolTeamKey,

            'poolView' => $poolView,
            'poolSlotView' => $poolNameView,
            'poolTypeView' => $poolTypeView,
            'poolTeamView' => $poolTeamView,
            'poolTeamSlotView' => $poolSlot,

            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,

            'regTeamId' => null,
        ];
        $this->gameConn->insert('poolTeams', $poolTeam);
    }

    private function assignRegTeamsToPoolPlayTeams(
        $commit
    ) {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectId = $this->projectId;
        foreach ($this->programs as $program) {
            foreach ($this->ages[$program] as $age) {
                foreach ($this->genders as $gender) {

                    // Fetch the reg teams
                    $sql = 'SELECT regTeamId,teamName FROM regTeams WHERE projectId = ? AND program = ? AND age = ? AND gender = ?';
                    $stmt = $this->regTeamConn->executeQuery($sql, [$projectId, $program, $age, $gender]);
                    $regTeams = $stmt->fetchAll();

                    // Fetch the pool teams
                    $sql = 'SELECT poolTeamId FROM poolTeams WHERE projectId = ? AND program = ? AND age = ? AND gender = ? AND poolTypeKey = \'PP\'';
                    $stmt = $this->gameConn->executeQuery($sql, [$projectId, $program, $age, $gender]);
                    $poolTeams = $stmt->fetchAll();

                    if (count($regTeams) !== count($poolTeams)) {
                        die('RegTeam PoolTeam count mismatch');
                    }
                    $teamCount = count($regTeams);
                    foreach ($regTeams as $regTeam) {
                        $regTeamId = $regTeam['regTeamId'];
                        $tryAgain = true;
                        while ($tryAgain) {
                            $random = rand(0, $teamCount - 1);
                            if (!isset($poolTeams[$random]['regTeamId'])) {

                                $this->gameConn->update(
                                    'poolTeams',
                                    ['regTeamId' => $regTeamId, 'regTeamName' => $regTeam['teamName']],
                                    ['poolTeamId' => $poolTeams[$random]['poolTeamId']]
                                );
                                $poolTeams[$random]['regTeamId'] = $regTeamId;
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

    private function initGames(
        $commit
    ) {
        if (!$commit) {
            return;
        }
        // Six fields per division
        $fieldSlots = [
            [1, 'Fri', '07:30', 'A5', 'A2', 'B'],
            [2, 'Fri', '07:30', 'A1', 'A4', 'B'],
            [3, 'Fri', '07:30', 'A6', 'A3', 'B'],
            [4, 'Fri', '07:30', 'C5', 'C2', 'B'],
            [5, 'Fri', '07:30', 'C1', 'C4', 'B'],
            [6, 'Fri', '07:30', 'C6', 'C3', 'B'],
            [1, 'Fri', '08:45', 'D5', 'D2', 'B'],
            [2, 'Fri', '08:45', 'D1', 'D4', 'B'],
            [3, 'Fri', '08:45', 'D6', 'D3', 'B'],
            [4, 'Fri', '08:45', 'B5', 'B2', 'B'],
            [5, 'Fri', '08:45', 'B1', 'B4', 'B'],
            [6, 'Fri', '08:45', 'B6', 'B3', 'B'],

            [1, 'Fri', '10:45', 'A2', 'A6', 'B'],
            [2, 'Fri', '10:45', 'A5', 'A1', 'B'],
            [3, 'Fri', '10:45', 'A4', 'A3', 'B'],
            [4, 'Fri', '10:45', 'C2', 'C6', 'B'],
            [5, 'Fri', '10:45', 'C5', 'C1', 'B'],
            [6, 'Fri', '10:45', 'C4', 'C3', 'B'],
            [1, 'Fri', '12:00', 'D2', 'D6', 'B'],
            [2, 'Fri', '12:00', 'D5', 'D1', 'B'],
            [3, 'Fri', '12:00', 'D4', 'D3', 'B'],
            [4, 'Fri', '12:00', 'B2', 'B6', 'B'],
            [5, 'Fri', '12:00', 'B5', 'B1', 'B'],
            [6, 'Fri', '12:00', 'B4', 'B3', 'B'],

            [1, 'Fri', '13:15', 'A5', 'A2', 'G'],
            [2, 'Fri', '13:15', 'A1', 'A4', 'G'],
            [3, 'Fri', '13:15', 'A6', 'A3', 'G'],
            [4, 'Fri', '13:15', 'C5', 'C2', 'G'],
            [5, 'Fri', '13:15', 'C1', 'C4', 'G'],
            [6, 'Fri', '13:15', 'C6', 'C3', 'G'],
            [1, 'Fri', '14:30', 'D5', 'D2', 'G'],
            [2, 'Fri', '14:30', 'D1', 'D4', 'G'],
            [3, 'Fri', '14:30', 'D6', 'D3', 'G'],
            [4, 'Fri', '14:30', 'B5', 'B2', 'G'],
            [5, 'Fri', '14:30', 'B1', 'B4', 'G'],
            [6, 'Fri', '14:30', 'B6', 'B3', 'G'],

            [1, 'Fri', '16:30', 'A2', 'A6', 'G'],
            [2, 'Fri', '16:30', 'A5', 'A1', 'G'],
            [3, 'Fri', '16:30', 'A4', 'A3', 'G'],
            [4, 'Fri', '16:30', 'C2', 'C6', 'G'],
            [5, 'Fri', '16:30', 'C5', 'C1', 'G'],
            [6, 'Fri', '16:30', 'C4', 'C3', 'G'],
            [1, 'Fri', '17:45', 'D2', 'D6', 'G'],
            [2, 'Fri', '17:45', 'D5', 'D1', 'G'],
            [3, 'Fri', '17:45', 'D4', 'D3', 'G'],
            [4, 'Fri', '17:45', 'B2', 'B6', 'G'],
            [5, 'Fri', '17:45', 'B5', 'B1', 'G'],
            [6, 'Fri', '17:45', 'B4', 'B3', 'G'],

            [1, 'Sat', '08:00', 'A3', 'A2', 'G'],
            [2, 'Sat', '08:00', 'A1', 'A6', 'G'],
            [3, 'Sat', '08:00', 'A4', 'A5', 'G'],
            [4, 'Sat', '08:00', 'C3', 'C2', 'G'],
            [5, 'Sat', '08:00', 'C1', 'C6', 'G'],
            [6, 'Sat', '08:00', 'C4', 'C5', 'G'],
            [1, 'Sat', '09:15', 'D3', 'D2', 'G'],
            [2, 'Sat', '09:15', 'D1', 'D6', 'G'],
            [3, 'Sat', '09:15', 'D4', 'D5', 'G'],
            [4, 'Sat', '09:15', 'B3', 'B2', 'G'],
            [5, 'Sat', '09:15', 'B1', 'B6', 'G'],
            [6, 'Sat', '09:15', 'B4', 'B5', 'G'],

            [1, 'Sat', '10:30', 'A3', 'A2', 'B'],
            [2, 'Sat', '10:30', 'A1', 'A6', 'B'],
            [3, 'Sat', '10:30', 'A4', 'A5', 'B'],
            [4, 'Sat', '10:30', 'C3', 'C2', 'B'],
            [5, 'Sat', '10:30', 'C1', 'C6', 'B'],
            [6, 'Sat', '10:30', 'C4', 'C5', 'B'],
            [1, 'Sat', '11:45', 'D3', 'D2', 'B'],
            [2, 'Sat', '11:45', 'D1', 'D6', 'B'],
            [3, 'Sat', '11:45', 'D4', 'D5', 'B'],
            [4, 'Sat', '11:45', 'B3', 'B2', 'B'],
            [5, 'Sat', '11:45', 'B1', 'B6', 'B'],
            [6, 'Sat', '11:45', 'B4', 'B5', 'B'],

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
        $projectId = $this->projectId;
        $this->gameConn->delete('games', ['projectId' => $projectId]);
        $this->gameConn->delete('gameTeams', ['projectId' => $projectId]);
        $this->gameConn->delete('gameOfficials', ['projectId' => $projectId]);

        $count = 0;
        foreach ($this->programs as $program) {
            $gameNumberProgram = 0;
            switch ($program) {
                case 'Core':
                    $gameNumberProgram = 10000;
                    break;
                case 'Extra':
                    $gameNumberProgram = 20000;
                    break;
                case 'Club':
                    $gameNumberProgram = 30000;
                    break;
                case 'VIP':
                    $gameNumberProgram = 40000;
                    break;
            }
            foreach ($this->ages[$program] as $age) {
                //$fieldNumberStart = substr($age,1) * 10;
                foreach ($this->genders as $gender) {

                    $gameNumber = 0;
                    switch ($program) {

                        case 'Core':
                        case 'Extra':
                            $gameNumber = $gameNumberProgram + (substr($age, 0, 2) * 100);
                            break;
                        case 'VIP':
                            $gameNumber = $gameNumberProgram + 100;
                            break;
                        case 'Club':
                            $gameNumber = $gameNumberProgram + (substr($age, 2) * 100);
                            break;
                    }
                    if ($gender === 'G') {
                        $gameNumber += 1000;
                    }
                    foreach ($fieldSlots as $fieldSlot) {
                        list($fieldNumber, $dow, $time, $home, $away, $fieldSlotGender) = $fieldSlot;
                        if ($fieldSlotGender === $gender) {
                            //$fieldNumber = $fieldNumberStart + $fieldNumber;
                            $gameNumber++;
                            $this->initGame(
                                $projectId,
                                $program,
                                $age,
                                $gender,
                                $gameNumber,
                                $fieldNumber,
                                $dow,
                                $time,
                                $home,
                                $away
                            );

                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\rLoading Games %5d", $count);
                            }
                            //if ($age === '19U') {
                            //    echo sprintf("Game Number %d %s%s\n",$gameNumber,$age,$gender);
                            //}
                        }
                    }
                }
            }
        }
        echo sprintf("\rLoaded Games %5d      \n", $count);
    }

    private function initGame(
        $projectId,
        $program,
        $age,
        $gender,
        $gameNumber,
        $fieldNumber,
        $dow,
        $time,
        $home,
        $away
    ) {
        // Filter D games
        if ($home[0] === 'D') {
            $pools = $this->getPools($gender.$age);
            if (count($pools) < 4) {
                return;
            }
        }
        $dates = [
            'Fri' => '2018-07-13',
            'Sat' => '2018-07-14',
            'Sun' => '2018-07-15',
        ];
        $start = $dates[$dow].' '.$time.':00';

        $lengths = [
            'VIP' => 20 + 5,
            '10U' => 40 + 5,
            '11U' => 50 + 5,
            '12U' => 50 + 5,
            '13U' => 50 + 10,
            '14U' => 50 + 10,
            '16U' => 60 + 10,
            '19U' => 60 + 10,
            '2008' => 40 + 5,
            '2007' => 40 + 5,
            '2006' => 50 + 5,
            '2005' => 50 + 5,
            '2004' => 50 + 5,
            '2003' => 60 + 5,
            '2002' => 60 + 5,
        ];
        // Hack in fieldName for 16U/19U Medal rounds
        $fieldName = $age.' - '.$fieldNumber;
        $poolType = substr($home, 0, 2);
        if ($poolType === 'QF' || $poolType === 'SF' || $poolType === 'TF') {
            if ($age === '16U' && $gender === 'G') {
                switch ($fieldName) {
                    case '16U - 3':
                        $fieldName = '16U - 1';
                        break;
                    case '16U - 4':
                        $fieldName = '16U - 2';
                        break;
                    case '16U - 5':
                        $fieldName = '16U - 3';
                        break;
                    case '16U - 6':
                        $fieldName = '16U - 4';
                        break;
                }
            }
            if ($age === '19U' && $gender === 'G') {
                switch ($fieldName) {
                    case '19U - 3':
                        $fieldName = '16U - 5';
                        break;
                    case '19U - 4':
                        $fieldName = '16U - 6';
                        break;
                    case '19U - 5':
                        $fieldName = '19U - 1';
                        break;
                    case '19U - 6':
                        $fieldName = '19U - 2';
                        break;
                }
            }
            if ($age === '16U' && $gender === 'B' && $poolType === 'QF') {
                switch ($fieldName) {
                    case '16U - 3':
                        $fieldName = '16U - 1';
                        break;
                    case '16U - 4':
                        $fieldName = '16U - 2';
                        break;
                    case '16U - 5':
                        $fieldName = '16U - 3';
                        break;
                    case '16U - 6':
                        $fieldName = '16U - 4';
                        break;
                }
            }
            if ($age === '19U' && $gender === 'B' && $poolType === 'QF') {
                switch ($fieldName) {
                    case '19U - 3':
                        $fieldName = '16U - 5';
                        break;
                    case '19U - 4':
                        $fieldName = '16U - 6';
                        break;
                    case '19U - 5':
                        $fieldName = '19U - 1';
                        break;
                    case '19U - 6':
                        $fieldName = '19U - 2';
                        break;
                }
            }
            if ($age === '19U' && $gender === 'B' && $poolType !== 'QF') {
                switch ($fieldName) {
                    case '19U - 1':
                        $fieldName = '16U - 5';
                        break;
                    case '19U - 2':
                        $fieldName = '16U - 6';
                        break;
                    case '19U - 3':
                        $fieldName = '19U - 1';
                        break;
                    case '19U - 4':
                        $fieldName = '19U - 2';
                        break;
                }
            }
        }
        // Add playing time to game entity?
        $finishDateTime = new \DateTime($start);
        $interval = sprintf('PT%dM', $lengths[$age]);
        $finishDateTime->add(new \DateInterval($interval));

        $gameId = $projectId.':'.$gameNumber;
        $game = [
            'gameId' => $gameId,
            'projectId' => $projectId,
            'gameNumber' => $gameNumber,
            'role' => 'game',
            'fieldName' => $fieldName,
            'venueName' => 'LNSC',

            'start' => $start,
            'finish' => $finishDateTime->format('Y-m-d H:i:s'),

            'state' => 'Published',
            'status' => 'Normal',
            'reportState' => 'Initial',
        ];
        $this->gameConn->insert('games', $game);

        // Game officials
        $isMedalRound = in_array(substr($home, 0, 2), ['QF', 'SF', 'TF']);
        $gameOfficial = [
            'projectId' => $projectId,
            'gameId' => $gameId,
            'gameNumber' => $gameNumber,
            'assignRole' => $isMedalRound ? 'ROLE_ASSIGNOR' : 'ROLE_REFEREE',
            'assignState' => 'Open',
        ];
        foreach ([1, 2, 3] as $slot) {
            $gameOfficial['gameOfficialId'] = $gameId.':'.$slot;
            $gameOfficial['slot'] = $slot;
            $this->gameConn->insert('gameOfficials', $gameOfficial);
        }
        // Game Teams
        $gameTeam = [
            'projectId' => $projectId,
            'gameId' => $gameId,
            'gameNumber' => $gameNumber,
            'poolTeamId' => null,
        ];
        $isPoolPlay = !in_array(substr($home, 0, 2), ['QF', 'SF', 'TF', 'ZZ']);
        foreach ([1, 2] as $slot) {
            $team = $slot === 1 ? $home : $away;

            $poolTeamName = $isPoolPlay ? 'PP'.$team : $team;

            $poolTeamId = sprintf('%s:%s%s%s%s', $projectId, $gender, $age, $program, $poolTeamName);

            $gameTeam['gameTeamId'] = $gameId.':'.$slot;
            $gameTeam['slot'] = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('gameTeams', $gameTeam);

        }
    }
}