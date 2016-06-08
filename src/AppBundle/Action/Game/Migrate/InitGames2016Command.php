<?php
namespace AppBundle\Action\Game\Migrate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class InitGames2016Command extends Command
{
    private $gameConn;
    private $regTeamConn;

    public function __construct(Connection $ng2016GamesConn)
    {
        parent::__construct();

        $this->gameConn    = $ng2016GamesConn;
        $this->regTeamConn = $ng2016GamesConn;
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

        $this->initRegTeams($commit || false);

        $this->initPoolTeams($commit || false);

        $this->assignRegTeamsToPoolPlayTeams($commit || false);

        $this->initGames($commit || true);

        echo sprintf("Init Games NG2016 Completed.\n");
    }

    private $projectId = 'AYSONationalGames2016';
    private $programs  = ['Core'];
    private $genders   = ['B', 'G'];
    private $ages      = ['U10', 'U12', 'U14', 'U16', 'U19'];

    private function getPools($division)
    {
        switch($division) {
            case 'U10G':
            case 'U16B':
            case 'U19G':
                return ['A','B','C'];
        }
        return ['A','B','C','D'];
    }
    private function initRegTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $this->regTeamConn->delete('regTeams', ['projectId' => $this->projectId]);

        $count = 0;
        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($age . $gender);
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

    private function initRegTeam($projectId, $program, $age, $gender, $teamNumber)
    {
        $division = $age . $gender;

        $teamKey = sprintf('%s-%s-%02d', $division, $program, $teamNumber);

        $regTeamId = $projectId . ':' . $teamKey;

        $regTeam = [
            'regTeamId'  => $regTeamId,
            'projectId'  => $projectId,
            'teamKey'    => $teamKey,
            'teamNumber' => $teamNumber,
            'teamName'   => sprintf('#%02d', $teamNumber),
            'teamPoints' => null,
            
            'orgId'    => null,
            'orgView'  => null,
            
            'program'  => $program,
            'gender'   => $gender,
            'age'      => $age,
            'division' => $division,
        ];
        $this->regTeamConn->insert('regTeams', $regTeam);
    }

    private function initPoolTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectId = $this->projectId;
        $this->gameConn->delete('poolTeams', ['projectId' => $projectId]);
        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($age . $gender);
                    foreach ($pools as $poolName) {
                        foreach ([1, 2, 3, 4, 5, 6] as $poolTeamName) {
                            $poolTeamSlot = $poolName . $poolTeamName;
                            $this->initPoolTeam($projectId, 'PP', $poolName, $poolTeamName, $poolTeamSlot, $program, $age, $gender);
                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\rLoading Pool Teams %5d", $count);
                            }
                        }
                    }
                    $medalRoundPools = [
                        'QF' => [
                            '1' => [['game' =>  1, 'name' => 'X', 'slot' => 'A 1st'], ['game' =>  1, 'name' => 'Y', 'slot' => 'C 2nd']],
                            '2' => [['game' =>  2, 'name' => 'X', 'slot' => 'B 1st'], ['game' =>  2, 'name' => 'Y', 'slot' => 'D 2nd']],
                            '3' => [['game' =>  3, 'name' => 'X', 'slot' => 'C 1st'], ['game' =>  3, 'name' => 'Y', 'slot' => 'A 2nd']],
                            '4' => [['game' =>  4, 'name' => 'X', 'slot' => 'D 1st'], ['game' =>  4, 'name' => 'Y', 'slot' => 'B 2nd']],
                        ],
                        'SF' => [
                            '1' => [['game' =>  5, 'name' => 'X', 'slot' => 'QF1 Win'], ['game' =>  5, 'name' => 'Y', 'slot' => 'QF2 Win']],
                            '2' => [['game' =>  6, 'name' => 'X', 'slot' => 'QF3 Win'], ['game' =>  6, 'name' => 'Y', 'slot' => 'QF4 Win']],
                            '3' => [['game' =>  9, 'name' => 'X', 'slot' => 'QF1 Los'], ['game' =>  9, 'name' => 'Y', 'slot' => 'QF2 Los']],
                            '4' => [['game' => 10, 'name' => 'X', 'slot' => 'QF3 Los'], ['game' => 10, 'name' => 'Y', 'slot' => 'QF4 Los']],
                        ],
                        'TF' => [
                            '1' => [['game' =>  7, 'name' => 'X', 'slot' => 'SF5 Win'], ['game' =>  7, 'name' => 'Y', 'slot' => 'SF6 Win']],
                            '2' => [['game' =>  8, 'name' => 'X', 'slot' => 'SF5 Los'], ['game' =>  8, 'name' => 'Y', 'slot' => 'SF6 Los']],
                            '3' => [['game' => 11, 'name' => 'X', 'slot' => 'SF9 Win'], ['game' => 11, 'name' => 'Y', 'slot' => 'SF10 Win']],
                            '4' => [['game' => 12, 'name' => 'X', 'slot' => 'SF9 Los'], ['game' => 12, 'name' => 'Y', 'slot' => 'SF10 Los']],
                        ],
                        'ZZ' => [ // Two teams is probably enough, could add 12 teams per pool, decide later
                            '01-12' => [['name' => 'X', 'slot' => 'Team 1',], ['name' => 'Y', 'slot' => 'Team 2']],
                            '13-24' => [['name' => 'X', 'slot' => 'Team 1',], ['name' => 'Y', 'slot' => 'Team 2']],
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
                                    $projectId, $poolType, $poolName, $poolTeam['name'], $poolTeam['slot'],
                                    $program, $age, $gender, $game
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

    private function initPoolTeam($projectId, $poolType, $poolName, $poolTeamName, $poolSlot, $program, $age, $gender, $game = null)
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
        $poolNameView = $game ? sprintf('%2s',$game): $poolName;
        switch($poolType) {
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
        $poolView     = sprintf('%s-%s %s %s', $age, $gender, $poolTypeDesc, $poolNameView);
        $poolTeamView = sprintf('%s-%s %s %s', $age, $gender, $poolTypeDesc, $poolSlot);

        switch($game) {
            case 5: case 6: case 7: case 8:
                $bracket = 'Championship';
                break;
            case 9: case 10: case 11: case 12:
                $bracket = 'Consolation';
                break;
            default:
                $bracket = null;
        }
        // Append bracket, probably should use new line here, adjust view later
        $poolView = $bracket ? $poolView . '<br>' . $bracket : $poolView;

        $poolKey     = sprintf('%s%s%s%s',   $division, $program, $poolType, $poolName);
        $poolTeamKey = sprintf('%s%s%s%s%s', $division, $program, $poolType, $poolName, $poolTeamName);

        $poolTeamId = $projectId . ':' . $poolTeamKey;

        $poolTeam = [
            'poolTeamId' => $poolTeamId,
            'projectId'  => $projectId,

            'poolKey'     => $poolKey,
            'poolTypeKey' => $poolType,
            'poolTeamKey' => $poolTeamKey,

            'poolView'     => $poolView,
            'poolSlotView' => $poolNameView,
            'poolTypeView' => $poolTypeView,
            'poolTeamView' => $poolTeamView,
            'poolTeamSlotView' => $poolSlot,

            'program'  => $program,
            'gender'   => $gender,
            'age'      => $age,
            'division' => $division,

            'regTeamId' => null,
        ];
        $this->gameConn->insert('poolTeams', $poolTeam);
    }

    private function assignRegTeamsToPoolPlayTeams($commit)
    {
        if (!$commit) {
            return;
        }
        $count = 0;
        $projectId = $this->projectId;
        foreach ($this->programs as $program) {
            foreach ($this->ages as $age) {
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

                                $this->gameConn->update('poolTeams',
                                    ['regTeamId'  => $regTeamId, 'regTeamName' => $regTeam['teamName']],
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

    private function initGames($commit)
    {
        if (!$commit) {
            return;
        }
        // Six fields per division
        $fieldSlots = [
            [1,'Wed','08:00','ZZ01-12X','ZZ01-12Y','B'],[2,'Wed','08:00','ZZ01-12X','ZZ01-12Y','B'],[3,'Wed','08:00','ZZ01-12X','ZZ01-12X','B'],
            [4,'Wed','08:00','ZZ01-12X','ZZ01-12Y','B'],[5,'Wed','08:00','ZZ01-12X','ZZ01-12Y','B'],[6,'Wed','08:00','ZZ01-12X','ZZ01-12Y','B'],
            [1,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],[2,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],[3,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],
            [4,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],[5,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],[6,'Wed','09:00','ZZ13-24X','ZZ13-24Y','B'],

            [1,'Wed','10:00','ZZ01-12X','ZZ01-12Y','G'],[2,'Wed','10:00','ZZ01-12X','ZZ01-12Y','G'],[3,'Wed','10:00','ZZ01-12X','ZZ01-12X','G'],
            [4,'Wed','10:00','ZZ01-12X','ZZ01-12Y','G'],[5,'Wed','10:00','ZZ01-12X','ZZ01-12Y','G'],[6,'Wed','10:00','ZZ01-12X','ZZ01-12Y','G'],
            [1,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],[2,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],[3,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],
            [4,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],[5,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],[6,'Wed','11:00','ZZ13-24X','ZZ13-24Y','G'],

            [1,'Wed','12:00','ZZ01-12X','ZZ01-12Y','B'],[2,'Wed','12:00','ZZ01-12X','ZZ01-12Y','B'],[3,'Wed','12:00','ZZ01-12X','ZZ01-12X','B'],
            [4,'Wed','12:00','ZZ01-12X','ZZ01-12Y','B'],[5,'Wed','12:00','ZZ01-12X','ZZ01-12Y','B'],[6,'Wed','12:00','ZZ01-12X','ZZ01-12Y','B'],
            [1,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],[2,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],[3,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],
            [4,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],[5,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],[6,'Wed','13:00','ZZ13-24X','ZZ13-24Y','B'],

            [1,'Wed','14:00','ZZ01-12X','ZZ01-12Y','G'],[2,'Wed','14:00','ZZ01-12X','ZZ01-12Y','G'],[3,'Wed','14:00','ZZ01-12X','ZZ01-12X','G'],
            [4,'Wed','14:00','ZZ01-12X','ZZ01-12Y','G'],[5,'Wed','14:00','ZZ01-12X','ZZ01-12Y','G'],[6,'Wed','14:00','ZZ01-12X','ZZ01-12Y','G'],
            [1,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],[2,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],[3,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],
            [4,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],[5,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],[6,'Wed','15:00','ZZ13-24X','ZZ13-24Y','G'],

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
            [1, 'Fri', '09:15', 'D5', 'D2', 'B'], [2, 'Fri', '09:15', 'D1', 'D4', 'B'], [3, 'Fri', '09:15', 'D6', 'D3', 'B'],
            [4, 'Fri', '09:15', 'B5', 'B2', 'B'], [5, 'Fri', '09:15', 'B1', 'B4', 'B'], [6, 'Fri', '09:15', 'B6', 'B3', 'B'],

            [1, 'Fri', '10:30', 'A5', 'A2', 'G'], [2, 'Fri', '10:30', 'A1', 'A4', 'G'], [3, 'Fri', '10:30', 'A6', 'A3', 'G'],
            [4, 'Fri', '10:30', 'C5', 'C2', 'G'], [5, 'Fri', '10:30', 'C1', 'C4', 'G'], [6, 'Fri', '10:30', 'C6', 'C3', 'G'],
            [1, 'Fri', '11:45', 'D5', 'D2', 'G'], [2, 'Fri', '11:45', 'D1', 'D4', 'G'], [3, 'Fri', '11:45', 'D6', 'D3', 'G'],
            [4, 'Fri', '11:45', 'B5', 'B2', 'G'], [5, 'Fri', '11:45', 'B1', 'B4', 'G'], [6, 'Fri', '11:45', 'B6', 'B3', 'G'],

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
            [1, 'Sat', '09:15', 'D3', 'D2', 'G'], [2, 'Sat', '09:15', 'D1', 'D6', 'G'], [3, 'Sat', '09:15', 'D4', 'D5', 'G'],
            [4, 'Sat', '09:15', 'B3', 'B2', 'G'], [5, 'Sat', '09:15', 'B1', 'B6', 'G'], [6, 'Sat', '09:15', 'B4', 'B5', 'G'],

            [1, 'Sat', '10:30', 'A3', 'A2', 'B'], [2, 'Sat', '10:30', 'A1', 'A6', 'B'], [3, 'Sat', '10:30', 'A4', 'A5', 'B'],
            [4, 'Sat', '10:30', 'C3', 'C2', 'B'], [5, 'Sat', '10:30', 'C1', 'C6', 'B'], [6, 'Sat', '10:30', 'C4', 'C5', 'B'],
            [1, 'Sat', '11:45', 'D3', 'D2', 'B'], [2, 'Sat', '11:45', 'D1', 'D6', 'B'], [3, 'Sat', '11:45', 'D4', 'D5', 'B'],
            [4, 'Sat', '11:45', 'B3', 'B2', 'B'], [5, 'Sat', '11:45', 'B1', 'B6', 'B'], [6, 'Sat', '11:45', 'B4', 'B5', 'B'],

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
        $this->gameConn->delete('games',         ['projectId' => $projectId]);
        $this->gameConn->delete('gameTeams',     ['projectId' => $projectId]);
        $this->gameConn->delete('gameOfficials', ['projectId' => $projectId]);

        $count = 0;
        foreach ($this->programs as $program) {
            $gameNumberProgram = 0;
            switch ($program) {
                case 'Core':
                    $gameNumberProgram = 10000;
                    break;
            }
            foreach ($this->ages as $age) {
                //$fieldNumberStart = substr($age,1) * 10;
                foreach ($this->genders as $gender) {

                    $gameNumber = $gameNumberProgram + (substr($age, 1) * 100);
                    if ($gender === 'G') {
                        $gameNumber += 2000;
                    }
                    foreach ($fieldSlots as $fieldSlot) {
                        list($fieldNumber, $dow, $time, $home, $away, $fieldSlotGender) = $fieldSlot;
                        if ($fieldSlotGender === $gender) {
                            //$fieldNumber = $fieldNumberStart + $fieldNumber;
                            $gameNumber++;
                            $this->initGame($projectId, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away);

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
    private function initGame($projectId, $program, $age, $gender, $gameNumber, $fieldNumber, $dow, $time, $home, $away)
    {
        // Filter D games
        if ($home[0] === 'D') {
            $pools = $this->getPools($age . $gender);
            if (count($pools) < 4) {
                return;
            }
        }
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
        // Hack in fieldName for U16/U19 Medal rounds
        $fieldName = $age . ' - ' . $fieldNumber;
        $poolType = substr($home,0,2);
        if ($poolType === 'QF' || $poolType === 'SF' || $poolType === 'TF') {
            if ($age === 'U16' && $gender === 'G') {
                switch($fieldName) {
                    case 'U16 - 3': $fieldName = 'U16 - 1'; break;
                    case 'U16 - 4': $fieldName = 'U16 - 2'; break;
                    case 'U16 - 5': $fieldName = 'U16 - 3'; break;
                    case 'U16 - 6': $fieldName = 'U16 - 4'; break;
                }
            }
            if ($age === 'U19' && $gender === 'G') {
                switch($fieldName) {
                    case 'U19 - 3': $fieldName = 'U16 - 5'; break;
                    case 'U19 - 4': $fieldName = 'U16 - 6'; break;
                    case 'U19 - 5': $fieldName = 'U19 - 1'; break;
                    case 'U19 - 6': $fieldName = 'U19 - 2'; break;
                }
            }
            if ($age === 'U16' && $gender === 'B' && $poolType === 'QF') {
                switch($fieldName) {
                    case 'U16 - 3': $fieldName = 'U16 - 1'; break;
                    case 'U16 - 4': $fieldName = 'U16 - 2'; break;
                    case 'U16 - 5': $fieldName = 'U16 - 3'; break;
                    case 'U16 - 6': $fieldName = 'U16 - 4'; break;
                }
            }
            if ($age === 'U19' && $gender === 'B' && $poolType === 'QF') {
                switch($fieldName) {
                    case 'U19 - 3': $fieldName = 'U16 - 5'; break;
                    case 'U19 - 4': $fieldName = 'U16 - 6'; break;
                    case 'U19 - 5': $fieldName = 'U19 - 1'; break;
                    case 'U19 - 6': $fieldName = 'U19 - 2'; break;
                }
            }
            if ($age === 'U19' && $gender === 'B' && $poolType !== 'QF') {
                switch($fieldName) {
                    case 'U19 - 1': $fieldName = 'U16 - 5'; break;
                    case 'U19 - 2': $fieldName = 'U16 - 6'; break;
                    case 'U19 - 3': $fieldName = 'U19 - 1'; break;
                    case 'U19 - 4': $fieldName = 'U19 - 2'; break;
                }
            }
        }
        // Add playing time to game entity?
        $finishDateTime = new \DateTime($start);
        $interval = sprintf('PT%dM',$lengths[$age]);
        $finishDateTime->add(new \DateInterval($interval));

        $gameId = $projectId . ':' . $gameNumber;
        $game = [
            'gameId'     => $gameId,
            'projectId'  => $projectId,
            'gameNumber' => $gameNumber,
            'role'       => 'game',
            'fieldName'  => $fieldName,
            'venueName'  => 'Polo',

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
            'gameId'      => $gameId,
            'gameNumber'  => $gameNumber,
            'assignRole'  => $isMedalRound ? 'ROLE_ASSIGNOR' : 'ROLE_REFEREE',
            'assignState' => 'Open',
        ];
        foreach([1,2,3] as $slot) {
            $gameOfficial['gameOfficialId']   = $gameId . ':' . $slot;
            $gameOfficial['slot'] = $slot;
            $this->gameConn->insert('gameOfficials',$gameOfficial);
        }
        // Game Teams
        $gameTeam = [
            'projectId'  => $projectId,
            'gameId'     => $gameId,
            'gameNumber' => $gameNumber,
            'poolTeamId' => null,
        ];
        $isPoolPlay = !in_array(substr($home,0,2),['QF','SF','TF','ZZ']);
        foreach([1,2] as $slot)
        {
            $team = $slot === 1 ? $home : $away;

            $poolTeamName = $isPoolPlay ? 'PP' . $team : $team;

            $poolTeamId = sprintf('%s:%s%s%s%s', $projectId, $age, $gender, $program, $poolTeamName);

            $gameTeam['gameTeamId'] = $gameId . ':' . $slot;
            $gameTeam['slot']       = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('gameTeams',$gameTeam);
        }
    }
}