<?php

namespace AppBundle\Action\Game\ImportAffinitySchedule;

use AppBundle\Common\ExcelReaderTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportAffinityScheduleReaderExcel
{
    use ExcelReaderTrait;

    private $projectId;
    private $venueName;
    private $date;
    private $field;
    private $games = [];
    private $regTeams = [];
    private $poolTeams = [];

    public function __construct($projectId, $venueName)
    {
        $this->projectId = $projectId;
        $this->venueName = $venueName;
    }

    public function read($filename)
    {
        // Tosses exception
        /** @var  Xlsx $reader */
        $reader = IOFactory::createReaderForFile($filename);

        // Need this otherwise dates and such are returned formatted
        $reader->setReadDataOnly(true);

        // Just grab all the rows
        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);
        $rows = $ws->toArray();
        array_shift($rows); // Discard header line

        foreach ($rows as $row) {
            $this->processRow($row);
        }
die();
        return;
    }

    protected function processRow($row)
    {
        $colDate = 0;
        $colField = 0;
        $colGameNumber = 0;
        $colFlight = 1;
        $colRound = 2;
        $colGame = 3;
        $colGameTime = 4;
        $colHomeTeamName = 5;
        $colAwayTeamName = 6;

        if (!$this->projectId) {
            return;
        }

        //skip blank rows
        if (empty($row)) {

            return;
        }

        //if row is date
        if (is_string($row[$colDate]) && is_bool(strpos($row[$colField], $this->venueName))) {
            $date = date('Y-m-d', strtotime($row[$colDate]));
            $this->date = $this->processDate($date);

            return;
        }
//        var_dump($this->date);

        //if row is field
        if (strpos($row[$colField], $this->venueName) > -1) {
            $r = preg_replace("/[^a-zA-Z0-9\s]/", '', $row[$colField]);
            $data = explode(' ', $r);
            $this->field = $data[3];

            return;
        }
//        var_dump($this->field);

        //else read game from row
        $gameNumber = trim($row[$colGameNumber]);
        if (!$gameNumber) {
            return null;
        }

        $flight = explode('-', preg_replace('/\s+/', '', trim($row[$colFlight])));

        $program = null;
        $gender = null;
        $division = null;
        $age = null;
        switch (count($flight)) {
            case 1:
                $flight = explode(' ', trim($row[$colFlight]));
                switch ($flight[0]) {
                    case 'Club':
                        $program = $flight[0];
                        $division = 'Adult';
                        $gender = $flight[1];
                        $age = $flight[2];
                        break;
                    case 'Adult':
                        $program = $flight[0];
                        $gender = $flight[1];
                        if(isset($flight[2])){
                            $division = $flight[2];
                        } else {
                            $division = $flight[1];
                        }
                        $age = $flight[0];
                        break;
                    default:
                        var_dump($flight);
                        die('Unrecognized Flight');
                }
                break;
            case 2:
                $division = $flight[0];
                $program = $flight[1];
                $gender = substr($flight[0], 0, 1);
                $age = substr($flight[0], 1, 3);
                break;
            default:

        }

        $round = trim($row[$colRound]);
        $poolTypeView = null;
        switch ($round) {
            case 'Bracket':
                $poolTypeView = 'PP';
                break;
            case 'Semi-Final':
                $poolTypeView = 'SF';
                break;
            case'Final':
                $poolTypeView = 'FIN';
                break;
        }

        $gameTime = explode(' -- ', trim($row[$colGameTime]));
        $start = strtotime($gameTime[0].'M');
        $gameStart = date('H:i:s',$start);
        $finish = strtotime($gameTime[1].'M');
        $gameFinish = date('H:i:s',$finish);

        $game = trim($row[$colGame]);
        $pools = explode(' vs ', $game);

        $homePoolTeamSlotView = $pools[0];
        $poolTeam = str_split($pools[0]);
        $homePool = $poolTeam[0];
        $homePoolSlot = $poolTeam[1];
        $homeTeamName = trim($row[$colHomeTeamName]);
        $homePoolTeamKey = $division.'-'.$program.'-'.$poolTypeView.'-'.$homePoolTeamSlotView;
        $homeTeamKey

        $awayPoolTeamSlotView = $pools[1];
        $poolTeam = str_split($pools[1]);
        $awayPool = $poolTeam[0];
        $awayPoolSlot = $poolTeam[1];
        $awayTeamName = trim($row[$colAwayTeamName]);
        $awayPoolTeamKey = $division.'-'.$program.'-'.$poolTypeView.'-'.$awayPoolTeamSlotView;

        //build game
        $game = [
            'projectId' => $this->projectId,
            'gameNumber' => $gameNumber,
            'gameId' => $this->projectId.':'.abs($gameNumber),
            'date' => $this->date,
            'time' => $gameStart,
            'start' => $this->date.' '.$gameStart,
            'finish' => $this->date.' '.$gameFinish,
            'fieldName' => $this->field,
            'homePoolTeamKey' => $homePoolTeamKey,
            'awayPoolTeamKey' => $awayPoolTeamKey,
            'homePoolTeamId' => $this->projectId.':'.$homePoolTeamKey,
            'awayPoolTeamId' => $this->projectId.':'.$awayPoolTeamKey,
            'homeTeamName' => $homeTeamName,
            'awayTeamName' => $awayTeamName,
        ];

        var_dump($game);

        $this->games[] = $game;

        //build registered Teams
        //Home
        $team = [
            'regTeamId' => $this->projectId.':'.$homePoolTeamKey,
            'projectId' => $this->projectId,
            'teamKey' => $division.':'.$gender.':'.$program.':'.$homePoolTeamSlotView,
            'teamNumber' => $homePoolTeamSlotView,
            'teamName' => $homeTeamName,
            'teamPoints' => 0,
            'orgId' => null,
            'orgView' => null,
            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $this->projectId.':'.$awayPoolTeamKey,
        ];

//        var_dump($team);

        $this->regTeams[] = $team;

        //Away
        $team = [
            'regTeamId' => $gameNumber,
            'projectId' => $this->projectId,
            'teamKey' => $this->projectId.':'.abs($gameNumber),
            'teamNumber' => $this->date,
            'teamName' => $gameStart,
            'teamPoints' => $this->date.' '.$gameStart,
            'orgId' => $this->date.' '.$gameFinish,
            'orgView' => $this->field,
            'program' => $homePoolTeamKey,
            'gender' => $awayPoolTeamKey,
            'age' => $this->projectId.':'.$homePoolTeamKey,
            'division' => $this->projectId.':'.$awayPoolTeamKey,
        ];

//        var_dump($team);

        $this->regTeams[] = $team;

        //build pools Teams
        $pool = [
            'projectId' => $this->projectId,
            'gameNumber' => $gameNumber,
            'gameId' => $this->projectId.':'.abs($gameNumber),
            'date' => $this->date,
            'time' => $gameStart,
            'start' => $this->date.' '.$gameStart,
            'finish' => $this->date.' '.$gameFinish,
            'venuName' => $this->venueName,
            'fieldName' => $this->field,
            'homePoolTeamKey' => $homePoolTeamKey,
            'awayPoolTeamKey' => $awayPoolTeamKey,
            'homePoolTeamId' => $this->projectId.':'.$homePoolTeamKey,
            'awayPoolTeamId' => $this->projectId.':'.$awayPoolTeamKey,
            'homeTeamName' => $homeTeamName,
            'awayTeamName' => $awayTeamName,
        ];

//        var_dump($pool);

        $this->poolTeams[] = $pool;

    }

    public function getGames()
    {
        return $this->games;
    }

    public function getRegTeams()
    {
        return $this->regTeams;
    }

    public function getPoolTeams()
    {
        return $this->poolTeams;
    }
}