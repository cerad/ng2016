<?php

namespace AysoBundle\Load;

use PHPExcel_Style_NumberFormat;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Abstract;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setName('affinity:transform')
            ->setDescription('Transform Affinity Schedule Export to Import XLSX files')
            ->addArgument('filename', InputArgument::REQUIRED, 'Affinity Schedule File');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $this->path_parts = pathinfo($filename);

        $this->ts = date("Ymd_His");
        $this->contentsFilename = $this->getCSVFilename('AffinityBaseValues');

        $this->load($filename);

        $games = $this->loadGames($this->dataValues);
        $regTeams = $this->loadRegTeams($this->dataValues);

//        TODO: $poolTeams

//        TODO: $gameOfficials

        $contents = $this->prepOutFile($this->dataValues);
        $this->writeCSV($contents, $this->contentsFilename);

    }

//file timestamp
    private $ts;
    private $path_parts;

    private function getCSVFilename($name)
    {
        $path = sprintf(
            '%s/%s_%s_%s.csv',
            $this->path_parts ['dirname'],
            $this->ts,
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
                if (!is_null($data[0])) {
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
            var_dump($data);
            echo "\n";
        }

    }

    private $gameDate;
    private $gameField;

    private $dataKeys = array(
        'ProjectId',
        'Date',
        'Field',
        'GameNum',
        'Program',
        'Gender',
        'Age',
        'Division',
        'PType',
        'HomePSlot',
        'AwayPSlot',
        'HomeTSlot',
        'AwayTSlot',
        'StartTime',
        'FinishTime',
        'HomeTeamName',
        'AwayTeamName',
        'HomeTeamKey',
        'AwayTeamKey',
        'HomePoolKey',
        'AwayPoolKey',
        'HomeTeamNumber',
        'AwayTeamNumber',
    );

    private $dataValues;

    private $regTeamsKeys = array(
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
    private $regTeamsValues;

    private $poolTeamsKeys = array(
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
        'regTeamPoints',
    );

    private $poolTeamsValues;

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

    private $gamesValues;

    private $gameTeamsKeys = array(
        'gameTeamId',
        'gameNumber',
        'projectId',
        'gameId',
        'gameNumber',
        'slot',
        'poolTeamId',
        'results',
        'resultsDetail',
        'pointsScored',
        'pointsAllowed',
        'pointsEarned',
        'pointsDeducted',
        'sportsmanship',
        'injuries',
        'misconduct',
    );

    private $gameTeamsValues;

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

    private $gameOfficialsValues;

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
                    $poolType = 'TF';
                    break;
                case'Final':
                    $poolType = 'FM';
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
            $homePoolKey = $division.$program.$homeTeamSlot;
            $awayPoolKey = $division.$program.$awayTeamSlot;

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
                $homeTeamKey,
                $awayTeamKey,
                $homePoolKey,
                $awayPoolKey,
                $homeTeamNumber,
                $awayTeamNumber,
            ];

            $this->dataValues[] = array_combine($this->dataKeys, $dataValues);
        }

        return;
    }

    private function loadGames($data)
    {
        if (empty($data)) {
            return null;
        }

        $games = null;
        //set the data : game in each row
        foreach ($data as $row) {
            $game = (object)array_combine($this->dataKeys, $row);

            $games[] = array(
                $game->ProjectId.':'.$game->GameNum,
                $game->ProjectId,
                $game->GameNum,
                'game',
                $game->Field,
                $this->venue,
                sprintf('%s %s', $game->Date, $game->StartTime),
                sprintf('%s %s', $game->Date, $game->FinishTime),
                'Published',
                'Normal',
                null,
                'Initial',
            );
        }

        if (!is_null($games)) {
            //delete old data from table
            $sql = 'DELETE FROM games WHERE `projectId` = ?';
            $deleteGamesStmt = $this->nocGamesConn->prepare($sql);
            $deleteGamesStmt->execute([$this->projectId]);

            //load new data
            $sql = 'INSERT INTO games (gameId, projectId, gameNumber, role, fieldName, venueName, `start`, finish, state, `status`, reportText, reportState) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
            $insertGamesStmt = $this->nocGamesConn->prepare($sql);
            foreach ($games as $game) {
                $insertGamesStmt->execute($game);
            }
            //get new data
            $sql = 'SELECT * FROM games WHERE `projectId` = ?';
            $selectGamesStmt = $this->nocGamesConn->prepare($sql);

            $selectGamesStmt->execute([$this->projectId]);

            $games = $selectGamesStmt->fetchAll();
        }

        return $games;
    }

    private function loadRegTeams($data)
    {
        if (empty($data)) {
            return null;
        }

        //delete old data from table
        $sql = 'DELETE FROM regTeams WHERE projectId = ?';
        $deleteRegTeamsStmt = $this->nocGamesConn->prepare($sql);
        $deleteRegTeamsStmt->execute([$this->projectId]);

        $teams = null;
        $gameTeams = ['home', 'away'];

        //set the data : game in each row
        foreach ($data as $row) {
            $teams = (object)array_combine($this->dataKeys, $row);

            foreach ($gameTeams as $gameTeam) {
                switch ($gameTeam) {
                    case 'home':
                        $regTeamId = $teams->ProjectId.':'.$teams->HomeTeamKey;
                        $teamKey = $teams->HomeTeamKey;
                        $teamName = $teams->HomeTeamName;
                        $teamNumber = $teams->HomeTeamNumber;
                        break;
                    default:
                        $regTeamId = $teams->ProjectId.':'.$teams->AwayTeamKey;
                        $teamKey = $teams->AwayTeamKey;
                        $teamName = $teams->AwayTeamName;
                        $teamNumber = $teams->AwayTeamNumber;
                }

                $rTeam = array(
                    $regTeamId,
                    $teams->ProjectId,
                    $teamKey,
                    $teamNumber,
                    $teamName,
                    0,
                    null,
                    null,
                    $teams->Program,
                    $teams->Gender,
                    $teams->Age,
                    $teams->Division,
                );

                if($teams->PType == 'PP') {
                    //load new data
                    $sql = 'SELECT * FROM regTeams WHERE regTeamId = ?';
                    $checkRegTeamStmt = $this->nocGamesConn->prepare($sql);
                    $checkRegTeamStmt->execute([$regTeamId]);
                    $t = $checkRegTeamStmt->fetch();
                    if (!$t) {
                        $sql = 'INSERT INTO regTeams (regTeamId, projectId, teamKey, teamNumber, teamName, teamPoints, orgId, orgView, program, gender, age, division) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                        $insertRegTeamsStmt = $this->nocGamesConn->prepare($sql);
                        $insertRegTeamsStmt->execute($rTeam);
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


    private function writeCSV(
        $data,
        $filename
    ) {

//        // Not sure this is needed
//        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());
//
        if (is_null($data)) {
            return;
        }

        $k = 0;
        $fp = fopen($filename, 'w');

        foreach ($data as $row) {
            fputcsv($fp, $row);
            $k += 1;
        }

        fclose($fp);
        echo sprintf("%d rows written to %s\n", $k, $filename);

        return;
    }

    protected function clearDatabase(
        Connection $conn
    ) {
        $databaseName = $conn->getDatabase();
        $conn->exec('TRUNCATE TABLE games');
        $conn->exec('TRUNCATE TABLE gameOfficials');
        $conn->exec('TRUNCATE TABLE gameTeams');
        $conn->exec('TRUNCATE TABLE poolTeams');
        $conn->exec('TRUNCATE TABLE poolTeams');
    }

}