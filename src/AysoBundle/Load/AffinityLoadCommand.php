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

class AffinityLoadCommand extends Command
{
    private $project;
    private $projectId;

    private $venueName;

    private $regTeamFilename;
    private $poolTeamFilename;
    private $gameScheduleFilename;
    private $contentsFilename;

    public function __construct(
        $project,
        $venueName
    ) {
        parent::__construct();

        $this->project = $project['info'];
        $this->venueName = $venueName;

        $this->projectId = $this->project['key'];

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
        $this->regTeamFilename = $this->getCSVFilename('RegTeams');
        $this->poolTeamFilename = $this->getCSVFilename('PoolTeams');
        $this->gameScheduleFilename = $this->getCSVFilename('GameSchedule');

        $this->contentsFilename = $this->getCSVFilename('AffinityBaseValues');

        $this->load($filename);

    }

//file timestamp
    private $ts;
    private $path_parts;

    private function getCSVFilename($name)
    {
        return $this->path_parts ['dirname'].'/'.$this->ts.'_'.$name.'_'.$this->path_parts ['filename'].'.csv';
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

//            foreach ($this->dataValues as $data) {
//                var_dump($data);
//            };

            echo sprintf("Processed %4d rows\n", $row - 1);

//            $this->regTeams = $this->getRegTeams($data);
//            var_dump($regTeams);

//            $this->poolTeams = $this->getPoolTeams($data);
//            var_dump($poolTeams);

            $contents = $this->prepOutFile($this->dataValues);
            $this->writeCSV($contents, $this->contentsFilename);

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
        'division'.
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
            $gameStart = date("H:i", strtotime($gameTime[0].'M'));
            $gameFinish = date("H:i", strtotime($gameTime[1].'M'));

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

            $homeTeamKey = $division.$program.sprintf('%02d', $homeTeamNumber);
            $awayTeamKey = $division.$program.sprintf('%02d', $awayTeamNumber);
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
            ];

            $this->dataValues[] = array_combine($this->dataKeys, $dataValues);
        }

        return;
    }

    private function getRegTeams($data)
    {
        if (empty($data)) {
            return null;
        }

        //set the header labels
        $regTeams = $this->regTeamsKeys;

        foreach($data as $row) {
            if (isset)
        }

        return $regTeams;
    }

    private function getPoolTeams($data)
    {

        if (empty($data)) {
            return null;
        }

        //set the header labels
        $poolTeams[] = $this->poolTeamsKeys;

        //set the data : game in each row
        foreach($data as $row) {
            $poolTeams[] = array(
            );

        }

        return $poolTeams;
    }

    private function getGames($data)
    {
        if (empty($data)) {
            return null;
        }

        //set the header labels
        $games[] = $this->gamesKeys;

        //set the data : game in each row
        foreach ($data as $row) {
            $games[] = $row;
        }

        return $games;
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

}