<?php

namespace AysoBundle\Load;

use PHPExcel_IOFactory;
use PHPExcel_Reader_Abstract;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Exception;

use Doctrine\DBAL\Connection;

class AffinityLoadCommand extends Command
{
    private $project;
    private $projectId;

    private $venueName;
    private $venue;

    private $games;

    /** @var  Connection */
    private $nocGamesConn;

    private $contentsFilename;

    private $commit;

    public function __construct(
        $project,
        Connection $nocGamesConn
    ) {
        parent::__construct();

        $this->project = $project['info'];
        $this->projectId = $this->project['key'];
        $this->venueName = $this->project['venue_name'];;
        $this->venue = $this->project['venue'];;

        $this->nocGamesConn = $nocGamesConn;

    }

    protected function configure()
    {
        $this
            ->setName('affinity:load')
            ->setDescription('Load Affinity Schedule Export to Import XLSX files')
            ->addArgument('filename', InputArgument::REQUIRED, 'Affinity Schedule File')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update')
            ->addOption('outfile', 'o', InputOption::VALUE_NONE, 'Export to output file')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $delete = $input->getOption('delete');
        $writeCSV = $input->getOption('outfile');
        $this->commit = $input->getOption('commit');

        $this->path_parts = pathinfo($filename);

        $this->contentsFilename = $this->getCSVFilename('AffinityBaseValues');

        $this->load($filename);

        if ($writeCSV) {
            $contents = $this->prepOutFile($this->dataValues);
            $this->writeCSV($contents, $this->contentsFilename);
            echo sprintf("Data written to %s ...\n", realpath($this->contentsFilename));
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

        echo "... Affinity transform complete.\n";

    }

    private $path_parts;

    private function getCSVFilename($name)
    {
        $ts = date("Ymd_His");

        $path = sprintf(
            '%s/%s_%s_%s.csv',
            $this->path_parts ['dirname'],
            $ts,
            $name,
            $this->path_parts
            ['filename']
        );

        return $path;
    }

    private function load($filename)
    {
        /* ====================================
        /*  Affinity Schedule Export Fields // expected values

            [ 0]=> "GameNum" // ["Thursday, July 13, 2017", "Lancaster National - 19       (8A -- 6P)", "7249"]
            [ 1]=> "Flight" // ["B10U - Core", "B10U - Extra", ""Club Girls 03-04", "VIP"]
            [ 2]=> "Round" // ["Bracket", "Semi-Final", "Final"]
            [ 3]=> "Game" // ["A4 vs A2", "1st of Pool vs 4th of Pool", "2nd of Pool vs 3rd of Pool", "Winner SF Game# 7258
        vs Winner SF Game# 7259"]
            [ 4]=> "Game Time" // ["10:15A -- 11:15A"}
            [ 5]=> "Home Team"
            [ 6]=> "Away Team"
         */

        echo sprintf("Loading Affinity Schedule file: %s...\n", $filename);

        /** @var PHPExcel_Reader_Abstract $reader */
        $reader = PHPExcel_IOFactory::createReaderForFile($filename);
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

    private $gameDate;
    private $gameField;

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

    private $gameOfficialsKeys = array(
        'gameOfficialId',
        'projectId',
        'gameId',
        'gameNumber',
        'slot',
        'phyPersonId',
        'regPersonId',
        'regPersonName',
        'assignRole',
        'assignState',
    );

    private function processRow($row)
    {

        //read date from row[]
        $date = date('Y-m-d', strtotime($row[0]));

        if (in_array($date, $this->project['dates'])) {
            $this->gameDate = $date;

//            echo "Date: ".$this->gameDate."\n";

            return;

        }

        //read field from row[]
        if (strpos($row[0], $this->venueName) > -1) {
            //read Field from row[]
            $r = preg_replace("/[^a-zA-Z0-9\s]/", '', $row[0]);
            $fields = explode(' ', $r);
            $this->gameField = $fields[3];

//            echo 'Field = '.$this->gameField."\n";

            return;

        }

        //read game from row[]
        $fields = $row;
        if (count($fields) > 1) {
            $game_num = $fields[0];
            $flight = $fields[1];

            if (strpos($flight, 'Club') !== false) {
                // is club flight
                $flight = explode(' ', $fields[1]);
                $program = $flight[0];
                $gender = substr($flight[1], 0, 1);
                $division = $gender.$flight[2];
                $age = $flight[2];
            } else {
                if (strpos($flight, 'VIP') !== false) {
                    // is VIP
                    $program = $flight;
                    $gender = 'C';
                    $division = null;
                    $age = null;
                } else {
                    // is Core or Extra
                    $flight = explode(' - ', $fields[1]);
                    $program = $flight[1];
                    $gender = substr($flight[0], 0, 1);
                    $division = $flight[0];
                    $age = substr($flight[0], 1, 3);
                }
            }

            $round = $fields[2];
            $poolType = null;
            switch ($round) {
                case 'Bracket':
                    $poolType = 'PP';
                    break;
                case 'Semi-Final':
                    $poolType = 'SF';
                    break;
                case'Final':
                    $poolType = 'TF';
                    break;
            }

            $game = $fields[3];
            $poolTeamKeys = explode(' vs ', $game);

            $gameTime = explode(' -- ', $fields[4]);
            $gameStart = date("H:i:s", strtotime($gameTime[0].'M'));
            $gameFinish = date("H:i:s", strtotime($gameTime[1].'M'));

            if ($poolType == 'PP') {
                $homeTeamName = $fields[5];
                $homeTeamSlot = $poolTeamKeys[0];
                $home = str_split($homeTeamSlot);
                $homePoolSlot = $home[0];
                switch ($homePoolSlot) {
                    case 'A':
                        $numBase = 0;
                        break;
                    case 'B':
                        $numBase = 10;
                        break;
                    case 'C':
                        $numBase = 20;
                        break;
                    case 'D':
                        $numBase = 30;
                        break;
                }
                $homeTeamNumber = $numBase + $home[1];

                $awayTeamName = $fields[6];
                $awayTeamSlot = $poolTeamKeys[1];
                $away = str_split($awayTeamSlot);
                $awayPoolSlot = $away[0];
                switch ($awayPoolSlot) {
                    case 'A':
                        $numBase = 0;
                        break;
                    case 'B':
                        $numBase = 10;
                        break;
                    case 'C':
                        $numBase = 20;
                        break;
                    case 'D':
                        $numBase = 30;
                        break;
                }
                $awayTeamNumber = $numBase + $away[1];
            } else {
                $homeTeamName = $poolTeamKeys[0];
                $homeTeamSlot = '1X';
                $homePoolSlot = '';
                $homeTeamNumber = '';

                $awayTeamName = $poolTeamKeys[1];
                $awayTeamSlot = '1Y';
                $awayPoolSlot = '';
                $awayTeamNumber = '';
            }

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
                $this->gameDate,
                $this->gameField,
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
            $sql = 'DELETE FROM games WHERE `projectId` = ?';
            $deleteGamesStmt = $this->nocGamesConn->prepare($sql);
            $deleteGamesStmt->execute([$this->projectId]);
        }

        try {
            if (!is_null($this->games)) {
                foreach ($this->games as $game) {
                    $gameId = $game[0];
                    $sql = 'SELECT * FROM games WHERE gameId = ?';
                    $checkRegTeamStmt = $this->nocGamesConn->prepare($sql);
                    $checkRegTeamStmt->execute([$gameId]);
                    $t = $checkRegTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO games (gameId, projectId, gameNumber, role, fieldName, venueName, `start`, finish, state, `status`, reportText, reportState) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                        $insertGamesStmt = $this->nocGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertGamesStmt->execute($game);
                        }
                        $countInserted += 1;
                    } else {
                        $assoGame = array_combine($this->gamesKeys, $game);
                        if (!empty(array_diff($t, $assoGame))) {
                            if ($this->commit) {
                                $this->nocGamesConn->update(
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

    private function loadRegTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM regTeams WHERE projectId = ?';
            $deleteRegTeamsStmt = $this->nocGamesConn->prepare($sql);
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

                if (!empty($teamName)) {
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
                            $checkRegTeamStmt = $this->nocGamesConn->prepare($sql);
                            $checkRegTeamStmt->execute([$regTeamId]);
                            $t = $checkRegTeamStmt->fetch();
                            if (!$t) {
                                //load new data
                                $sql = 'INSERT INTO regTeams (regTeamId, projectId, teamKey, teamNumber, teamName, teamPoints, orgId, orgView, program, gender, age, division) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                                $insertRegTeamsStmt = $this->nocGamesConn->prepare($sql);
                                if ($this->commit) {
                                    $insertRegTeamsStmt->execute($rTeam);
                                }
                                $countInserted += 1;
                            } else {
                                $assoRegTeam = array_combine($this->regTeamKeys, $rTeam);
                                if (!empty(array_diff($t, $assoRegTeam))) {
                                    if ($this->commit) {
                                        $this->nocGamesConn->update(
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
        }

        return array(
            'count' => $countInserted + $countUpdated,
            'inserted' => $countInserted,
            'updated' => $countUpdated,
        );
    }

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
            $deleteGameTeamsStmt = $this->nocGamesConn->prepare($sql);
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
                    $checkRegTeamStmt = $this->nocGamesConn->prepare($sql);
                    $checkRegTeamStmt->execute([$gameTeamId]);
                    $t = $checkRegTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO gameTeams (gameTeamId, projectId, gameId, gameNumber, slot, poolTeamId) 
                        VALUES (?,?,?,?,?,?)';
                        $insertGameTeamsStmt = $this->nocGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertGameTeamsStmt->execute($gTeam);
                            $countInserted += 1;
                        }
                    } else {
                        $assoGameTeam = array_combine($this->gameTeamKeys, $gTeam);
                        if (!empty(array_diff(array_slice($t, 0, 6), $assoGameTeam))) {
                            if ($this->commit) {
                                $this->nocGamesConn->update(
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
            $deletePoolTeamsStmt = $this->nocGamesConn->prepare($sql);
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
                    $checkPoolTeamStmt = $this->nocGamesConn->prepare($sql);
                    $checkPoolTeamStmt->execute([$poolTeamId]);
                    $t = $checkPoolTeamStmt->fetch();
                    if (!$t) {
                        $sql = 'INSERT INTO poolTeams (poolTeamId, projectId, poolKey, poolTypeKey, poolTeamKey, poolView, poolSlotView, poolTypeView, poolTeamView, poolTeamSlotView, sourcePoolKeys, sourcePoolSlot, program, gender, age, division, regTeamId, regTeamName) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                        $insertPoolTeamsStmt = $this->nocGamesConn->prepare($sql);
                        if ($this->commit) {
                            $insertPoolTeamsStmt->execute($pTeam);
                            $countInserted += 1;
                        }
                    } else {
                        $assoPoolTeam = array_combine($this->poolTeamKeys, $pTeam);
                        if (!empty(array_diff(array_slice($t, 0, 18), $assoPoolTeam))) {
                            if ($this->commit) {
                                $this->nocGamesConn->update(
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
            $deleteRegTeamsStmt = $this->nocGamesConn->prepare($sql);
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

        $count = 0;
        if (count($gOfficials) > 0) {
            try {
                foreach ($gOfficials as $official) {
                    $gameOfficialId = $official[0];
                    $sql = 'SELECT * FROM gameOfficials WHERE gameOfficialId = ?';
                    $checkPoolTeamStmt = $this->nocGamesConn->prepare($sql);
                    $checkPoolTeamStmt->execute([$gameOfficialId]);
                    $t = $checkPoolTeamStmt->fetch();
                    if (!$t) {
                        //load new data
                        $sql = 'INSERT INTO gameOfficials (gameOfficialId, projectId, gameId, gameNumber, slot, phyPersonId, regPersonId, regPersonName, assignRole, assignState) 
                            VALUES (?,?,?,?,?,?,?,?,?,?)';
                        $insertGameOfficialsStmt = $this->nocGamesConn->prepare($sql);
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