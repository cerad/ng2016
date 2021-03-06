<?php

namespace AppBundle\Action\Game\Admin;

use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AysoBundle\DataTransformer\TimeValueToTime;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL;

use DateTime;
use DateInterval;

class InitSOFNG2019Command extends Command
{
    private $gameConn;
    private $projectId;
    private $venue;
    private $dates;
    private $delete;
    private $fieldSlots = [];

    public function __construct(Connection $conn, string $projectId, array $project)
    {
        parent::__construct();

        $this->gameConn = $conn;
        $this->projectId = $projectId;
        $this->venue = $project['info']['venue'];
        $this->dates = $project['info']['dates'];

        date_default_timezone_set ( 'Europe/London' );
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ng2019:load:sof')
            ->setDescription('Init Soccerfest NG2019')
            ->addArgument('filename', InputArgument::REQUIRED, 'Affinity Schedule File')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws DBAL\DBALException
     * @throws DBAL\Exception\InvalidArgumentException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Init Soccerfest NG2019 ...\n");

        $filename = $input->getArgument('filename');
        $this->delete = $input->getOption('delete');
        $commit = $input->getOption('commit');

        $this->load($filename);

        $this->initRegTeams($commit);

        $this->initPoolTeams($commit);

        $this->assignRegTeamsToPoolPlayTeams($commit);

        $this->initGames($commit);

        echo sprintf("... Init Soccerfest NG2019 Completed.\n");
    }

    private $programs = ['Core'];
    private $genders = ['B', 'G'];
    private $ages = [
        'Core' => ['10U', '12U', '14U', '16U', '19U'],
    ];
    private $poolSize = [
        'B10U' => ['A' => 5, 'B' => 5, 'C' => 5, 'D' => 5],
        'G10U' => ['A' => 5, 'B' => 5, 'C' => 5, 'D' => 5],
        'B12U' => ['A' => 6, 'B' => 6, 'C' => 6, 'D' => 6],
        'G12U' => ['A' => 6, 'B' => 6, 'C' => 6, 'D' => 6],
        'B14U' => ['A' => 14],
        'G14U' => ['A' => 18],
        'B16U' => ['A' => 14],
        'G16U' => ['A' => 8],
        'B19U' => ['A' => 16],
        'G19U' => ['A' => 16],
    ];


    /**
     * @param $filename
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function load($filename)
    {
        /* ====================================
        /*  NG2019 Soccerfest Schedule Export Fields // expected values

            [0] => [Date]
            [1] => [Start Time ]
            [2] => [Location]
            [3] => [Division]
            [4] => [Home Team]
            [5] => [Away Team]
            [6] => [Home PoolTeamSlot]
            [7] => [Away PoolTeamSlot]

         */

        echo sprintf("Loading Soccerfest Schedule file: %s...\n", $filename);

        /** @var Xlsx $reader */
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);

        $rowMax = $ws->getHighestRow();
        $colMax = $ws->getHighestColumn();

        $data = null;
        try {
            for ($row = 2; $row <= $rowMax; $row++) {
                $range = sprintf('A%d:%s%d', $row, $colMax, $row);
                $data = $ws->rangeToArray($range, null, false, false, false)[0];
                if (!empty(trim($data[0]))) {
                    $this->processRow($data);

                    if (($row % 100) === 0) {
                        echo sprintf("\r  Processed %4d of %d rows", $row, $rowMax - 1);
                    }
                }
            }

            echo sprintf("\r%5d rows processed                 \n", $row - 2);

        } catch (Exception $e) {
            echo 'Exception: ', $e->getMessage(), "\n";
            $range = sprintf('A%d:%s%d', $row, $colMax, $row);
            echo 'Row Max: ', $rowMax, "\n";
            echo 'Column Max: ', $colMax, "\n";
            echo 'Row: ', $row, "\n";
            echo 'Range: ', $range, "\n";
            echo 'Data: ', "\n";
            var_dump($data); //
            echo "\n";
        }

        return;
    }

    /**
     * @param $row
     */
    private function processRow($row)
    {
        $xlTime = new TimeValueToTime();

        //read date from row[]
        $dow = date('D', strtotime($row[0]));
        $dt = $xlTime->transform($row[1]);
        $time = date("H:i", $dt);

        $field = (string)$row[2];
        $division = substr($row[3], 4, 1).substr($row[3], 0, 3);
        $homeTeam = $row[4];
        $awayTeam = $row[5];
        $homePoolTeamSlot = $this->generatePoolSlot($division, $homeTeam);
        $awayPoolTeamSlot = $this->generatePoolSlot($division, $awayTeam);

        $this->fieldSlots[] = [
            $dow,
            $time,
            $field,
            $division,
            $homePoolTeamSlot,
            $awayPoolTeamSlot,
        ];

        return;
    }


    /**
     * @param $division
     * @return mixed
     */
    private function getPools($division)
    {

        return $this->poolSize[$division];
    }

    /**
     * @param $commit
     * @throws DBAL\DBALException
     * @throws DBAL\Exception\InvalidArgumentException
     */
    private function initRegTeams($commit)
    {
        if ($this->delete) {
            $this->gameConn->delete('regTeams', ['projectId' => $this->projectId]);
        }

        if (!$commit) {
            return;
        }

        $count = 0;
        foreach ($this->programs as $program) {
            foreach ($this->ages[$program] as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($gender.$age);
                    $teamCount = 0;
                    foreach ($pools as $pool => $numTeams) {
                        $teamCount += $numTeams;
                    }
                    for ($teamNumber = 1; $teamNumber <= $teamCount; $teamNumber++) {
                        $this->initRegTeam($this->projectId, $program, $gender, $age, $teamNumber);
                        $count++;
                        if (($count % 100) === 0) {
                            echo sprintf("\r%5d Soccerfest Teams Loaded...", $count);
                        }
                    }

                }
            }
        }
        echo sprintf("\r%5d Soccerfest Teams Loaded         \n", $count);
    }

    /**
     * @param $projectId
     * @param $program
     * @param $gender
     * @param $age
     * @param $teamNumber
     * @throws DBAL\DBALException
     */
    private function initRegTeam(
        $projectId,
        $program,
        $gender,
        $age,
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
            'teamName' => sprintf('Team %d', $teamNumber),
            'teamPoints' => null,

            'orgId' => null,
            'orgView' => null,

            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,
        ];
        $this->gameConn->insert('regTeams', $regTeam);
    }

    /**
     * @param $commit
     * @throws DBAL\DBALException
     * @throws DBAL\Exception\InvalidArgumentException
     */
    private function initPoolTeams(
        $commit
    ) {
        if ($this->delete) {
            $this->gameConn->delete('poolTeams', ['projectId' => $this->projectId]);
        }
        if (!$commit) {
            return;
        }
        $count = 1;
        $projectId = $this->projectId;
        foreach ($this->programs as $program) {
            foreach ($this->ages[$program] as $age) {
                foreach ($this->genders as $gender) {
                    $pools = $this->getPools($gender.$age);
                    foreach ($pools as $pool => $poolTeamCount) {
                        for ($p = 0; $p < $poolTeamCount; $p++) {
                            $this->initPoolTeam(
                                $projectId,
                                'ZZ',
                                $pool,
                                $p + 1,
                                $program,
                                $age,
                                $gender
                            );

                            if (($count % 100) === 0) {
                                echo sprintf("\r%5d Soccerfest Pool Teams Loaded...", $count);
                            }
                            $count++;
                        }
                    }
                }

            }
        }

        echo sprintf("\r%5d Soccerfest Pool Teams Loaded        \n", $count - 1);
    }

    /**
     * @param $projectId
     * @param $poolType
     * @param $poolName
     * @param $poolSlot
     * @param $program
     * @param $age
     * @param $gender
     * @param null $game
     * @throws DBAL\DBALException
     */
    private function initPoolTeam(
        $projectId,
        $poolType,
        $poolName,
        $poolSlot,
        $program,
        $age,
        $gender,
        $game = null
    ) {
        $division = $gender.$age;
        $poolTypeKey = $poolType;
        switch ($poolType) {
            case 'ZZ':
                $poolTypeDesc = 'Soccerfest';
                $poolTypeView = 'SOF';
                break;
            default:
                $poolTypeDesc = 'UNKNOWN POOL DESC';
                $poolTypeView = 'UNK';
        }
        $poolTeamSlotView = $poolName.$poolSlot;

        // Append bracket, probably should use new line here, adjust view later

        $poolTeamId = sprintf('%s:%s%s%s%s', $projectId, $gender, $age, $program, $poolTeamSlotView);

        //$projectId

        $poolKey = sprintf('%s%s%s%s', $division, $program, $poolTypeView, $poolName);

        $poolTeamKey = sprintf('%s%s%s%s', $division, $program, $poolTypeView, $poolTeamSlotView);

        $poolNameView = $game ? sprintf('%2s', $game) : $poolName;

        $poolView = sprintf('%s%s %s %s %s', $gender, $age, $program, $poolTypeView, $poolName);

        $poolTeamView = sprintf('%s%s %s %s %s', $gender, $age, $program, $poolTypeView, $poolTeamSlotView);

        $poolTeam = [
            'poolTeamId' => $poolTeamId,
            'projectId' => $projectId,

            'poolKey' => $poolKey,
            'poolTypeKey' => $poolTypeKey,
            'poolTeamKey' => $poolTeamKey,

            'poolView' => $poolView,
            'poolSlotView' => $poolNameView,
            'poolTypeView' => $poolType,
            'poolTeamView' => $poolTeamView,
            'poolTeamSlotView' => $poolTeamSlotView,

            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,

            'regTeamId' => null,
        ];
        $this->gameConn->insert('poolTeams', $poolTeam);
    }

    /**
     * @param $commit
     * @throws DBAL\DBALException
     */
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
                    $stmt = $this->gameConn->executeQuery($sql, [$projectId, $program, $age, $gender]);
                    $regTeams = $stmt->fetchAll();

                    // Fetch the pool teams
                    $sql = 'SELECT poolTeamId FROM poolTeams WHERE projectId = ? AND program = ? AND age = ? AND gender = ? AND poolTypeKey = \'ZZ\'';
                    $stmt = $this->gameConn->executeQuery($sql, [$projectId, $program, $age, $gender]);
                    $poolTeams = $stmt->fetchAll();

                    if (count($regTeams) !== count($poolTeams)) {
                        var_dump($age, $gender, count($regTeams), count($poolTeams));
                        die('RegTeam PoolTeam count mismatch'."\n");
                    }

                    foreach ($regTeams as $key => $regTeam) {
                        $regTeamId = $regTeam['regTeamId'];
                        $tryAgain = true;
//                        while ($tryAgain) {
                        if (!isset($poolTeams['regTeamId'])) {
                            $this->gameConn->update(
                                'poolTeams',
                                ['regTeamId' => $regTeamId, 'regTeamName' => $regTeam['teamName']],
                                ['poolTeamId' => $poolTeams[$key]['poolTeamId']]
                            );
//                                $poolTeams['regTeamId'] = $regTeamId;
//                                $tryAgain = false;

                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\r%5d Soccerfest Teams Assigned...", $count);
                            }
                        }
//                        }
                    }
                }
            }
        }

        echo sprintf("\r%5d Soccerfest Teams Assigned            \n", $count);
    }

    /**
     * @param $commit
     * @throws DBAL\DBALException
     * @throws DBAL\Exception\InvalidArgumentException
     */
    private function initGames(
        $commit
    ) {
        if (!$commit) {
            return;
        }

        echo sprintf("\r  Loading Games...");
        $projectId = $this->projectId;
        if ($this->delete) {
            $this->gameConn->delete('games', ['projectId' => $projectId]);
            $this->gameConn->delete('gameTeams', ['projectId' => $projectId]);
            $this->gameConn->delete('gameOfficials', ['projectId' => $projectId]);
        }
        $count = 0;
        foreach ($this->programs as $program) {
            $gameNumberProgram = 10000;
            foreach ($this->ages[$program] as $age) {
                //$fieldNumberStart = substr($age,1) * 10;
                foreach ($this->genders as $gender) {
                    $gameNumber = $gameNumberProgram + (substr($age, 0, 2) * 100);
                    if ($gender === 'G') {
                        $gameNumber += 1000;
                    }
                    foreach ($this->fieldSlots as $fieldSlot) {
                        list(
                            $dow, $time, $field, $div, $homePoolTeamSlot, $awayPoolTeamSlot
                            ) =
                            $fieldSlot;
                        if ($div === $gender.$age) {
                            $gameNumber++;
                            $this->initGame(
                                $projectId,
                                $program,
                                $age,
                                $gender,
                                $gameNumber,
                                $field,
                                $dow,
                                $time,
                                $homePoolTeamSlot,
                                $awayPoolTeamSlot
                            );

                            $count++;
                            if (($count % 100) === 0) {
                                echo sprintf("\r%5d Games Loaded...    ", $count);
                            }
                        }
                    }
                }
            }
        }
        echo sprintf("\r%5d Games Loaded         \n", $count);
    }

    /**
     * @param $projectId
     * @param $program
     * @param $age
     * @param $gender
     * @param $gameNumber
     * @param $fieldNumber
     * @param $dow
     * @param $time
     * @param $homePoolTeamSlot
     * @param $awayPoolTeamSlot
     * @throws DBAL\DBALException
     * @throws Exception
     */
    private function initGame(
        $projectId,
        $program,
        $age,
        $gender,
        $gameNumber,
        $fieldNumber,
        $dow,
        $time,
        $homePoolTeamSlot,
        $awayPoolTeamSlot
    ) {
        $dates = $this->dates;

        $start = array_search($dow, $dates, true).' '.$time;

        $duration = 50 + 5;
        // Hack in fieldName for 16U/19U Medal rounds
        $fieldName = $fieldNumber;

        // Add playing time to game entity?
        $finishDateTime = new DateTime($start);
        $interval = sprintf('PT%dM', $duration);
        $finishDateTime->add(new DateInterval($interval));

        $gameId = $projectId.':'.$gameNumber;
        $game = [
            'gameId' => $gameId,
            'projectId' => $projectId,
            'gameNumber' => $gameNumber,
            'role' => 'game',
            'fieldName' => $fieldName,
            'venueName' => $this->venue,

            'start' => $start,
            'finish' => $finishDateTime->format('Y-m-d H:i:s'),

            'state' => 'Published',
            'status' => 'Normal',
            'reportState' => 'Initial',
        ];
        $this->gameConn->insert('games', $game);

        // Game officials
        $gameOfficial = [
            'projectId' => $projectId,
            'gameId' => $gameId,
            'gameNumber' => $gameNumber,
            'assignRole' => 'ROLE_REFEREE',
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

        foreach ([1, 2] as $slot) {
            $poolTeamSlot = $slot === 1 ? $homePoolTeamSlot : $awayPoolTeamSlot;

            $poolTeamId = sprintf('%s:%s%s%s%s', $projectId, $gender, $age, $program, $poolTeamSlot);

            $gameTeam['gameTeamId'] = $gameId.':'.$slot;
            $gameTeam['slot'] = $slot;
            $gameTeam['poolTeamId'] = $poolTeamId;
            $this->gameConn->insert('gameTeams', $gameTeam);

        }
    }

    /**
     * @param $div
     * @param $teamName
     * @return string
     */
    private function generatePoolSlot($div, $teamName)
    {
        $teamNumber = trim(substr($teamName, -2));
        $poolCode = 'A';
        $poolSlot = $teamNumber;
        $poolSizes = $this->poolSize[$div];

        foreach ($poolSizes as $pool => $poolSize) {
            $poolCode = $pool;
            if ($poolSlot > $poolSize) {
                $poolSlot -= $poolSize;
            } else {
                break;
            }
        }

        $poolTeamSlot = $poolCode.$poolSlot;

        return $poolTeamSlot;

    }
}