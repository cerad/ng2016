<?php

namespace AysoBundle\Load;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use DateTime;

use Exception;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class AffinityLoadCommand extends Command
{
    private $project;
    private $projectId;
    private $projectRoot;

    private $venueName;
    private $venue;

    private $games;

    /** @var  Connection */
    private $ngGamesConn;

    private $contentsFilename;

    private $commit;

    /**
     * AffinityLoadCommand constructor.
     * @param $project
     * @param Connection $ngGamesConn
     */
    public function __construct(
        $project,
        Connection $ngGamesConn
    ) {
        parent::__construct();

        $this->project = $project['info'];
        $this->projectId = $this->project['key'];
        $this->venueName = $this->project['venue_name'];
        $this->venue = $this->project['venue'];

        $this->ngGamesConn = $ngGamesConn;

        $this->projectRoot = realpath(__DIR__.'/../../../');

    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ng2019:affinity:load')
            ->setDescription('Load NG2019 Affinity Schedule Export to Import XLSX files')
            ->addArgument('filename', InputArgument::REQUIRED, 'NG2019 Affinity Schedule Excel File')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update')
            ->addOption('outfile', 'o', InputOption::VALUE_NONE, 'Export to output file')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws DBALException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $delete = $input->getOption('delete');
        $writeCSV = $input->getOption('outfile');
        $this->commit = $input->getOption('commit');

        $this->path_parts = pathinfo($filename);

        $this->contentsFilename = $this->getCSVFilename();

        $this->load($filename);

        if ($writeCSV) {
            $contents = $this->prepOutFile($this->dataValues);
            $this->writeCSV($contents, $this->contentsFilename);
            echo sprintf("Data written to %s ...\n", $this->contentsFilename);
        } else {

            $count = $this->loadRegTeams($this->dataValues, $delete);
            $i = $count['inserted'];
            $u = $count['updated'];
            echo "$i new regTeams loaded, $u existing regTeams updated...\n";

            $count = $this->loadGameTeams($this->dataValues, $delete);
            $i = $count['inserted'];
            $u = $count['updated'];
            echo "$i new gameTeams loaded, $u existing gameTeams updated...\n";

            $count = $this->loadPoolTeams($this->dataValues, $delete);
            $i = $count['inserted'];
            $u = $count['updated'];
            echo "$i new poolTeams loaded, $u existing poolTeams updated...\n";

            $count = $this->loadGames($this->dataValues, $delete);
            $i = $count['inserted'];
            $u = $count['updated'];
            echo "$i new games loaded, $u existing games updated...\n";

            $count = $this->loadGameOfficials($this->games, $delete);
            $i = $count['inserted'];
            echo "$i new gameOfficials loaded, existing gameOfficials are not updated...\n";
        }

        echo "... Affinity import complete.\n";

    }

    private $path_parts;

    /**
     * @param $name
     * @return string
     */
    private function getCSVFilename($name = '')
    {
        $ts = date("Ymd_His");

        $path = $this->projectRoot . sprintf(
            '/%s/%s_%s_%s.csv',
            $this->path_parts ['dirname'],
            $ts,
            $name,
            $this->path_parts
            ['filename']
        );

        return $path;
    }

    /**
     * @param $filename
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function load($filename)
    {
        /* ====================================
        /*  Affinity NG2019 Schedule Export Fields // expected values

        [01] => "Game #"
        [02] => "Venue"
        [03] => "Field #"
        [04] => "Field ID"
        [05] => "Date"
        [06] => "Day"
        [07] => "Time"
        [08] => "Flight"
        [09] => "Home Club"
        [10] => "Home Team"
        [11] => "Home Team Description"
        [12] => "Visitor Club"
        [13] => "Visitors"
        [14] => "Visitor Team Description"
        [15] => "Round Type Code"
        [16] => "Reschedule Reason"

         */

        echo sprintf("Loading NG2019 Affinity Schedule file: %s...\n", $filename);

        /** @var Xlsx $reader */
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);

        $rowMax = $ws->getHighestRow();
        $colMax = $ws->getHighestColumn();

        $data = null;
        try {
            for ($row = 2; $row < $rowMax; $row++) {
                $range = sprintf('A%d:%s%d', $row, $colMax, $row);
                $data = $ws->rangeToArray($range, null, false, false, false)[0];
                if (!empty((trim($data[0])))) {
                    $this->processRow($data);

                    if (($row % 100) === 0) {
                        echo sprintf("Processed %4d of %d rows\n", $row, $rowMax - 1);
                    }
                }

            }

            echo sprintf("Processed %4d rows\n", $row - 1);

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

    }

    private $dataKeys = array(
        'projectId',
        'date',
        'field',
        'gameNum',
        'program',
        'gender',
        'age',
        'division',
        'pType',
        'homePSlot',
        'awayPSlot',
        'homeTSlot',
        'awayTSlot',
        'startTime',
        'finishTime',
        'homeTeamName',
        'awayTeamName',
        'homeTeamNumber',
        'awayTeamNumber',
        'homeTeamKey',
        'awayTeamKey',
        'homePoolKey',
        'awayPoolKey',
        'homePoolTeamKey',
        'awayPoolTeamKey',
    );

    private $dataValues;

    private $gamesKeys = array(
        'gameId',
        'projectId',
        'gameNumber',
        'role',
        'fieldName',
        'venueName',
        'start',
        'finish',
        'state',
        'status',
        'reportText',
        'reportState',
    );

    private $regTeamKeys = array(
        'regTeamId',
        'projectId',
        'teamKey',
        'teamNumber',
        'teamName',
        'teamPoints',
        'orgId',
        'orgView',
        'program',
        'gender',
        'age',
        'division',
    );

    private $gameTeamKeys = array(
        'gameTeamId',
        'projectId',
        'gameId',
        'gameNumber',
        'slot',
        'poolTeamId',
    );

    private $poolTeamKeys = array(
        'poolTeamId',
        'projectId',
        'poolKey',
        'poolTypeKey',
        'poolTeamKey',
        'poolView',
        'poolSlotView',
        'poolTypeView',
        'poolTeamView',
        'poolTeamSlotView',
        'sourcePoolKeys',
        'sourcePoolSlot',
        'program',
        'gender',
        'age',
        'division',
        'regTeamId',
        'regTeamName',
    );

//    private $gameOfficialsKeys = array(
//        'gameOfficialId',
//        'projectId',
//        'gameId',
//        'gameNumber',
//        'slot',
//        'phyPersonId',
//        'regPersonId',
//        'regPersonName',
//        'assignRole',
//        'assignState',
//    );

    /**
     * @param $row
     * @throws Exception
     */
    private function processRow($row)
    {
        if (!is_numeric($row[0])) {
            return;

        }

        //read game from row[]
        if (count($row) > 1) {
            //read gameNumber from row[0]
            $game_num = $row[0];

            //read fieldNum from row[2]
            $gameField = $row[2];

            //read date from row[4]
            $gameDate = date('Y-m-d', strtotime($row[4]));

            //read time from row[6]

            $gameStart = date("H:i", strtotime($row[6]));
            $dt = new DateTime($gameStart);
            $dt = $dt->modify('+ 50 minutes');
            $gameFinish = $dt->format("H:i");

            //read division from row[7]
            $program = "Core";
            $division = $row[7];
            $gender = substr($division, -1);
            $age = substr($division, 0, 3);

            //read HomeTeam from row[9]
            $homeTeamName = strtoupper($row[9]);

            //read HomeTeamPoolSlot from row[10]
            $homeTeamSlot = $row[10];
            $homeTeamNumber = substr($homeTeamSlot, 1);
            $homePoolSlot = $homeTeamNumber;

            //read AwayTeam from row[12]
            $awayTeamName = strtoupper($row[12]);

            //read AwayTeamPoolSlot from row[13]
            $awayTeamSlot = $row[13];
            $awayTeamNumber = substr($awayTeamSlot, 1);
            $awayPoolSlot = $awayTeamNumber;

            //read $roundType from row[14]
            $roundType = $row[14];
            $poolType = null;
            switch ($roundType) {
                case 'Soccerfest':
                    $poolType = 'ZZ';
                    break;
                case 'B':
                    $poolType = 'PP';
                    break;
                case 'QF':
                    $poolType = 'QF';
                    break;
                case 'SF':
                    $poolType = 'SF';
                    break;
                case'C':
                    $poolType = 'CO';
                    break;
                case'F':
                    $poolType = 'FI';
                    break;
            }

            switch ($poolType) {
                case 'QF':
                case 'SF':
                case 'CO':
                case 'FI':
                    $homeTeamSlot = $game_num.'X';
                    $homePoolSlot = '';
                    $homeTeamNumber = '';

                    $awayTeamSlot = $game_num.'Y';
                    $awayPoolSlot = '';
                    $awayTeamNumber = '';
            }

            $homeTeamName = sprintf('%s', $homeTeamName); //ensure not NULL
            $awayTeamName = sprintf('%s', $awayTeamName);
            $homeTeamNumber = sprintf('%02d', $homeTeamNumber);
            $awayTeamNumber = sprintf('%02d', $awayTeamNumber);
            $homeTeamKey = $division.$program.$homeTeamNumber;
            $awayTeamKey = $division.$program.$awayTeamNumber;
            $homePoolKey = $division.$program.$poolType.$homePoolSlot;
            $awayPoolKey = $division.$program.$poolType.$awayPoolSlot;
            $homePoolTeamKey = $division.$program.$poolType.$homeTeamSlot;
            $awayPoolTeamKey = $division.$program.$poolType.$awayTeamSlot;

            $dataValues = [
                $this->projectId,
                $gameDate,
                $gameField,
                $game_num,
                $program,
                $gender,
                $age,
                $division,
                $poolType,
                $homePoolSlot,
                $awayPoolSlot,
                $homeTeamSlot,
                $awayTeamSlot,
                $gameStart,
                $gameFinish,
                $homeTeamName,
                $awayTeamName,
                $homeTeamNumber,
                $awayTeamNumber,
                $homeTeamKey,
                $awayTeamKey,
                $homePoolKey,
                $awayPoolKey,
                $homePoolTeamKey,
                $awayPoolTeamKey,
            ];

            $this->dataValues[] = array_combine($this->dataKeys, $dataValues);
        }

        return;
    }

    /**
     * @param $data
     * @param bool $delete
     * @return array|null
     * @throws DBALException
     */
    private function loadGames($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        $this->games = null;
        $countInserted = 0;
        $countUpdated = 0;

        //set the data : game in each row
        foreach ($data as $row) {
            $game = (object)array_combine($this->dataKeys, $row);

            $this->games[] = array(
                $game->projectId.':'.$game->gameNum,
                $game->projectId,
                $game->gameNum,
                'game',
                $game->field,
                $this->venue,
                sprintf('%s %s', $game->date, $game->startTime),
                sprintf('%s %s', $game->date, $game->finishTime),
                'Published',
                'Normal',
                null,
                'Initial',
            );
        }

        if ($delete) {
            //delete old data from table
            $sql = "DELETE FROM games WHERE `projectId` = ?";
            $deleteGamesStmt = $this->ngGamesConn->prepare($sql);
            $deleteGamesStmt->execute([$this->projectId]);
        }

        try {
            if (!is_null($this->games)) {
                foreach ($this->games as $game) {
                    $game = (array)$game;
                    $gameId = $game[0];
                    $sql = 'SELECT * FROM games WHERE gameId = ?';
                    $checkRegTeamStmt = $this->ngGamesConn->prepare($sql);
                    $checkRegTeamStmt->execute([$gameId]);
                    $t = $checkRegTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO games (gameId, projectId, gameNumber, role, fieldName, venueName, `start`, finish, state, `status`, reportText, reportState) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                        $insertGamesStmt = $this->ngGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertGamesStmt->execute($game);
                        }
                        $countInserted += 1;
                    } else {
                        $assoGame = array_combine($this->gamesKeys, $game);
                        if ($t != $assoGame) {
                            if ($this->commit) {
                                $this->ngGamesConn->update(
                                    'games',
                                    $assoGame,
                                    ['gameId' => $gameId]
                                );
                            }
                            $countUpdated += 1;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo sprintf("Line %s: %s\n", $e->getLine(), $e->getMessage());
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

    /**
     * @param $data
     * @param bool $delete
     * @return array|null
     * @throws DBALException
     */
    private function loadRegTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM regTeams WHERE projectId = ?';
            $deleteRegTeamsStmt = $this->ngGamesConn->prepare($sql);
            $deleteRegTeamsStmt->execute([$this->projectId]);
        }

        $teams = null;
        $gameTeams = ['home', 'away'];
        $countInserted = 0;
        $countUpdated = 0;

        //set the data : game in each row
        foreach ($data as $row) {
            $teams = (object)array_combine($this->dataKeys, $row);
            foreach ($gameTeams as $gameTeam) {
                switch ($gameTeam) {
                    case 'home':
                        $regTeamId = $teams->projectId.':'.$teams->homeTeamKey;
                        $teamKey = $teams->homeTeamKey;
                        $teamName = $teams->homeTeamName;
                        $teamNumber = $teams->homeTeamNumber;
                        break;
                    default:
                        $regTeamId = $teams->projectId.':'.$teams->awayTeamKey;
                        $teamKey = $teams->awayTeamKey;
                        $teamName = $teams->awayTeamName;
                        $teamNumber = $teams->awayTeamNumber;
                }

                $rTeam = array(
                    $regTeamId,
                    $teams->projectId,
                    $teamKey,
                    (string)(int)$teamNumber,
                    $teamName,
                    0,
                    null,
                    null,
                    $teams->program,
                    $teams->gender,
                    $teams->age,
                    $teams->division,
                );

                try {
                    if ($teams->pType == 'PP') {
                        $sql = 'SELECT * FROM regTeams WHERE regTeamId = ?';
                        $checkRegTeamStmt = $this->ngGamesConn->prepare($sql);
                        $checkRegTeamStmt->execute([$regTeamId]);
                        $t = $checkRegTeamStmt->fetch();
                        if (!$t) {
                            //load new data
                            $sql = 'INSERT INTO regTeams (regTeamId, projectId, teamKey, teamNumber, teamName, teamPoints, orgId, orgView, program, gender, age, division) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                            $insertRegTeamsStmt = $this->ngGamesConn->prepare($sql);
                            if ($this->commit) {
                                $insertRegTeamsStmt->execute($rTeam);
                            }
                            $countInserted += 1;
                        } else {
                            $assoRegTeam = array_combine($this->regTeamKeys, $rTeam);
                            if ($t != $assoRegTeam) {
                                if ($this->commit) {
                                    $this->ngGamesConn->update(
                                        'regTeams',
                                        $assoRegTeam,
                                        ['regTeamId' => $regTeamId]
                                    );
                                }
                                $countUpdated += 1;
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo sprintf("Line %s: %s\n", $e->getLine(), $e->getMessage());
                }
            }
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

    /**
     * @param $data
     * @param bool $delete
     * @return array|null
     * @throws DBALException
     */
    private function loadGameTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        $projectId = $this->projectId;
        $countInserted = 0;
        $countUpdated = 0;

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM gameTeams WHERE projectId = ?';
            $deleteGameTeamsStmt = $this->ngGamesConn->prepare($sql);
            $deleteGameTeamsStmt->execute([$projectId]);
        }
        $gTeams = null;
        $gameTeams = ['home', 'away'];

        //set the data : game in each row
        foreach ($data as $row) {
            $games = (object)array_combine($this->dataKeys, $row);

            foreach ($gameTeams as $gameTeam) {
                switch ($gameTeam) {
                    case 'home':
                        $slot = 1;
                        $poolTeamId = $projectId.':'.$games->homePoolTeamKey;
                        break;
                    default:
                        $slot = 2;
                        $poolTeamId = $projectId.':'.$games->awayPoolTeamKey;
                }

                $gameId = $projectId.':'.$games->gameNum;
                $gameTeamId = $gameId.':'.$slot;
                $gTeam = array(
                    $gameTeamId,
                    $projectId,
                    $gameId,
                    $games->gameNum,
                    $slot,
                    $poolTeamId,
                );

                try {
                    $sql = 'SELECT * FROM gameTeams WHERE gameTeamId = ?';
                    $checkRegTeamStmt = $this->ngGamesConn->prepare($sql);
                    $checkRegTeamStmt->execute([$gameTeamId]);
                    $t = $checkRegTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO gameTeams (gameTeamId, projectId, gameId, gameNumber, slot, poolTeamId) 
                        VALUES (?,?,?,?,?,?)';
                        $insertGameTeamsStmt = $this->ngGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertGameTeamsStmt->execute($gTeam);
                            $countInserted += 1;
                        }
                    } else {
                        $assoGameTeam = array_combine($this->gameTeamKeys, $gTeam);
                        if (array_slice($t, 0, 6) != $assoGameTeam) {
                            if ($this->commit) {
                                $this->ngGamesConn->update(
                                    'gameTeams',
                                    $assoGameTeam,
                                    ['gameTeamId' => $gameTeamId]
                                );
                            }
                            $countUpdated += 1;
                        }
                    }
                } catch (Exception $e) {
                    echo sprintf("Line %s: %s\n", $e->getLine(), $e->getMessage());
                }
            }
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

    /**
     * @param $data
     * @param bool $delete
     * @return array|null
     * @throws DBALException
     */
    private function loadPoolTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        $projectId = $this->projectId;
        $countInserted = 0;
        $countUpdated = 0;

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM poolTeams WHERE projectId = ?';
            $deletePoolTeamsStmt = $this->ngGamesConn->prepare($sql);
            $deletePoolTeamsStmt->execute([$projectId]);
        }

        $teams = null;
        $gameTeams = ['home', 'away'];

        //set the data : game in each row
        foreach ($data as $row) {
            $games = (object)array_combine($this->dataKeys, $row);

            foreach ($gameTeams as $gameTeam) {
                switch ($gameTeam) {
                    case 'home':
                        $poolTeamId = $projectId.':'.$games->homePoolTeamKey;
                        $poolKey = $games->homePoolKey;
                        $poolTeamKey = $games->homePoolTeamKey;
                        $pSlot = $games->homePSlot;
                        $tSlot = $games->homeTSlot;
                        $regTeamName = $games->homeTeamName;
                        $regTeamId = $projectId.':'.$games->homeTeamKey;
                        break;
                    default:
                        $poolTeamId = $projectId.':'.$games->awayPoolTeamKey;
                        $poolKey = $games->awayPoolKey;
                        $poolTeamKey = $games->awayPoolTeamKey;
                        $pSlot = $games->awayPSlot;
                        $tSlot = $games->awayTSlot;
                        $regTeamName = $games->awayTeamName;
                        $regTeamId = $projectId.':'.$games->awayTeamKey;
                }

                $poolViewBase = sprintf('%s %s %s ', $games->division, $games->program, $games->pType);
                $poolView = $poolViewBase.$pSlot;
                $poolSlotView = $pSlot;
                $poolTypeView = $games->pType;
                $poolTeamView = $poolViewBase.$tSlot;
                $poolTeamSlotView = $tSlot;

                $pTeam = array(
                    $poolTeamId,
                    $projectId,
                    $poolKey,
                    $games->pType,
                    $poolTeamKey,
                    $poolView,
                    $poolSlotView,
                    $poolTypeView,
                    $poolTeamView,
                    $poolTeamSlotView,
                    null,
                    null,
                    $games->program,
                    $games->gender,
                    $games->age,
                    $games->division,
                    $regTeamId,
                    $regTeamName,
                );

                //load new data
                try {
                    $sql = 'SELECT * FROM poolTeams WHERE poolTeamId = ?';
                    $checkPoolTeamStmt = $this->ngGamesConn->prepare($sql);
                    $checkPoolTeamStmt->execute([$poolTeamId]);
                    $t = $checkPoolTeamStmt->fetch();
                    if (!$t) {
                        $sql = 'INSERT INTO poolTeams (poolTeamId, projectId, poolKey, poolTypeKey, poolTeamKey, poolView, poolSlotView, poolTypeView, poolTeamView, poolTeamSlotView, sourcePoolKeys, sourcePoolSlot, program, gender, age, division, regTeamId, regTeamName) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                        $insertPoolTeamsStmt = $this->ngGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertPoolTeamsStmt->execute($pTeam);
                            $countInserted += 1;
                        }
                    } else {
                        $assoPoolTeam = array_combine($this->poolTeamKeys, $pTeam);
                        if (array_slice($t, 0, 18) != $assoPoolTeam) {
                            if ($this->commit) {
                                $this->ngGamesConn->update(
                                    'poolTeams',
                                    $assoPoolTeam,
                                    ['poolTeamId' => $poolTeamId]
                                );
                            }
                            $countUpdated += 1;
                        }
                    }
                } catch (Exception $e) {
                    echo sprintf("Line %s: %s\n", $e->getLine(), $e->getMessage());
                }
            }
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

    /**
     * @param $games
     * @param bool $delete
     * @return array|null
     * @throws DBALException
     */
    private function loadGameOfficials($games, $delete = false)
    {
        if (empty($games)) {
            return null;
        }

        $projectId = $this->projectId;
        $countInserted = 0;
        $countUpdated = 0;

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM gameOfficials WHERE projectId = ?';
            $deleteRegTeamsStmt = $this->ngGamesConn->prepare($sql);
            $deleteRegTeamsStmt->execute([$projectId]);
        }
        $gOfficials = null;
        //set the data : game in each row
        foreach ($games as $row) {
            $game = (object)array_combine($this->gamesKeys, $row);
            for ($slot = 1; $slot <= 3; $slot++) {
                $gameOfficialId = $game->gameId.':'.$slot;
                $gOfficials[] = array(
                    $gameOfficialId,
                    $projectId,
                    $game->gameId,
                    $game->gameNumber,
                    $slot,
                    null,
                    null,
                    null,
                    'ROLE_REFEREE',
                    'Open',
                );
            }
        }

        if (count($gOfficials) > 0) {
            try {
                foreach ($gOfficials as $official) {
                    $gameOfficialId = $official[0];
                    $sql = 'SELECT * FROM gameOfficials WHERE gameOfficialId = ?';
                    $checkPoolTeamStmt = $this->ngGamesConn->prepare($sql);
                    $checkPoolTeamStmt->execute([$gameOfficialId]);
                    $t = $checkPoolTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO gameOfficials (gameOfficialId, projectId, gameId, gameNumber, slot, phyPersonId, regPersonId, regPersonName, assignRole, assignState) 
                            VALUES (?,?,?,?,?,?,?,?,?,?)';
                        $insertGameOfficialsStmt = $this->ngGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertGameOfficialsStmt->execute($official);
                        }
                        $countInserted += 1;
                    }
                }
            } catch (Exception $e) {
                echo sprintf("Line %s: %s\n", $e->getLine(), $e->getMessage());
            }
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

    /**
     * @param $data
     * @return array|null
     */
    private function prepOutFile(
        $data
    ) {
        if (empty($data)) {
            return null;
        }

        //set the header labels
        $contents[] = $this->dataKeys;

        //set the data : game in each row
        foreach ($data as $row) {
            $contents[] = $row;
        }

        return $contents;
    }


    /**
     * @param $data
     * @param $filename
     * @return int|null
     */
    private function writeCSV($data, $filename)
    {
        if (is_null($data)) {
            return null;
        }

        $k = 0;
        $fp = fopen($filename, 'w');

        foreach ($data as $row) {
            fputcsv($fp, $row);
            $k += 1;
        }

        fclose($fp);

        return $k;
    }
}