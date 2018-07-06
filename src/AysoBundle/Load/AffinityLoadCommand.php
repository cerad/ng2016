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

    /** @var  Connection */
    private $nocGamesConn;

    private $contentsFilename;

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
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $delete = $input->getOption('delete');

        $this->path_parts = pathinfo($filename);

        $this->contentsFilename = $this->getCSVFilename('AffinityBaseValues');

        $this->load($filename);

        $regTeams = $this->loadRegTeams($this->dataValues, $delete);
        $count = count($regTeams);
        echo "$count regTeams loaded...\n";

        $gameTeams = $this->loadGameTeams($this->dataValues, $delete);
        $count = count($gameTeams);
        echo "$count gameTeams loaded...\n";

        $poolTeams = $this->loadPoolTeams($this->dataValues, $delete);
        $count = count($poolTeams);
        echo "$count poolTeams loaded...\n";

        $games = $this->loadGames($this->dataValues, $delete);
        $count = count($games);
        echo "$count games loaded...\n";

        $gameOfficials = $this->loadGameOfficials($games, $delete);
        $count = count($gameOfficials);
        echo "$count gameOfficials loaded...\n";

        echo "... Affinity transform complete.\n";

//        var_dump($this->dataValues[0]);

//        $contents = $this->prepOutFile($this->dataValues);
//        $this->writeCSV($contents, $this->contentsFilename);

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
                $homeTeamNumber = $home[1];

                $awayTeamName = $fields[6];
                $awayTeamSlot = $poolTeamKeys[1];
                $away = str_split($awayTeamSlot);
                $awayPoolSlot = $away[0];
                $awayTeamNumber = $away[1];
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

        $games = null;
        //set the data : game in each row
        foreach ($data as $row) {
            $game = (object)array_combine($this->dataKeys, $row);

            $games[] = array(
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

        if (!is_null($games)) {
            if ($delete) {
                //delete old data from table
                $sql = 'DELETE FROM games WHERE `projectId` = ?';
                $deleteGamesStmt = $this->nocGamesConn->prepare($sql);
                $deleteGamesStmt->execute([$this->projectId]);
            }
            //load new data
            try {
                $sql = 'INSERT INTO games (gameId, projectId, gameNumber, role, fieldName, venueName, `start`, finish, state, `status`, reportText, reportState) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                $insertGamesStmt = $this->nocGamesConn->prepare($sql);
                foreach ($games as $game) {
                    $insertGamesStmt->execute($game);
                }
            } catch (Exception $e) {

            }
        }
        //get new data
        $sql = 'SELECT * FROM games WHERE `projectId` = ?';
        $selectGamesStmt = $this->nocGamesConn->prepare($sql);

        $selectGamesStmt->execute([$this->projectId]);

        $games = $selectGamesStmt->fetchAll();

        return $games;
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
                        $teamNumber,
                        $teamName,
                        0,
                        null,
                        null,
                        $teams->program,
                        $teams->gender,
                        $teams->age,
                        $teams->division,
                    );

                    if ($teams->pType == 'PP') {
                        //load new data
                        $sql = 'SELECT * FROM regTeams WHERE regTeamId = ?';
                        $checkRegTeamStmt = $this->nocGamesConn->prepare($sql);
                        $checkRegTeamStmt->execute([$regTeamId]);
                        $t = $checkRegTeamStmt->fetch();
                        if (!$t) {
                            try {
                                $sql = 'INSERT INTO regTeams (regTeamId, projectId, teamKey, teamNumber, teamName, teamPoints, orgId, orgView, program, gender, age, division) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                                $insertRegTeamsStmt = $this->nocGamesConn->prepare($sql);
                                $insertRegTeamsStmt->execute($rTeam);
                            } catch (Exception $e) {

                            }
                        }
                    }
                }
            }
        }

        //get new data
        $sql = 'SELECT * FROM regTeams WHERE `projectId` = ?';
        $selectRegTeamsStmt = $this->nocGamesConn->prepare($sql);

        $selectRegTeamsStmt->execute([$this->projectId]);

        $regTeams = $selectRegTeamsStmt->fetchAll();

        return $regTeams;
    }

    private function loadGameTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        $projectId = $this->projectId;

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

                //load new data
                try {
                    $sql = 'INSERT INTO gameTeams (gameTeamId, projectId, gameId, gameNumber, slot, poolTeamId) 
                        VALUES (?,?,?,?,?,?)';
                    $insertGameTeamsStmt = $this->nocGamesConn->prepare($sql);
                    $insertGameTeamsStmt->execute($gTeam);
                } catch (Exception $e) {

                }
            }
        }

        //get new data
        $sql = 'SELECT * FROM gameTeams WHERE `projectId` = ?';
        $selectGameTeamsStmt = $this->nocGamesConn->prepare($sql);

        $selectGameTeamsStmt->execute([$this->projectId]);

        $gameTeams = $selectGameTeamsStmt->fetchAll();

        return $gameTeams;
    }

    private function loadPoolTeams($data, $delete = false)
    {
        if (empty($data)) {
            return null;
        }

        $projectId = $this->projectId;

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
                        $insertPoolTeamsStmt->execute($pTeam);
                    }
                } catch (Exception $e) {

                }
            }
        }

        //get new data
        $sql = 'SELECT * FROM poolTeams WHERE `projectId` = ?';
        $selectPoolTeamsStmt = $this->nocGamesConn->prepare($sql);

        $selectPoolTeamsStmt->execute([$projectId]);

        $poolTeams = $selectPoolTeamsStmt->fetchAll();

        return $poolTeams;
    }

    private function loadGameOfficials($games, $delete = false)
    {
        if (empty($games)) {
            return null;
        }

        $projectId = $this->projectId;

        if ($delete) {
            //delete old data from table
            $sql = 'DELETE FROM gameOfficials WHERE projectId = ?';
            $deleteRegTeamsStmt = $this->nocGamesConn->prepare($sql);
            $deleteRegTeamsStmt->execute([$projectId]);
        }
        $gameOfficials = null;

        //set the data : game in each row
        foreach ($games as $row) {
            $game = (object)array_combine($this->gamesKeys, $row);
            for ($slot = 1; $slot <= 3; $slot++) {
                $gameOfficialsId = $game->gameId.':'.$slot;
                $gOfficials[] = array(
                    $gameOfficialsId,
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

        //load new data
        if (count($gOfficials) > 0) {
            try {
                $sql = 'INSERT INTO gameOfficials (gameOfficialId, projectId, gameId, gameNumber, slot, phyPersonId, regPersonId, regPersonName, assignRole, assignState) 
                            VALUES (?,?,?,?,?,?,?,?,?,?)';
                $insertRegTeamsStmt = $this->nocGamesConn->prepare($sql);
                foreach ($gOfficials as $official) {
                    $insertRegTeamsStmt->execute($official);
                }
            } catch (Exception $e) {

            }
        }

        //get new data
        $sql = 'SELECT * FROM gameOfficials WHERE `projectId` = ?';
        $selectRegTeamsStmt = $this->nocGamesConn->prepare($sql);

        $selectRegTeamsStmt->execute([$projectId]);

        $gameOfficials = $selectRegTeamsStmt->fetchAll();

        return $gameOfficials;
    }

    private function prepOutFile($data)
    {
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

//        // Not sure this is needed
//        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());
//
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