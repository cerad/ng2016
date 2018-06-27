<?php
/**
 * Created by PhpStorm. to fill poolTeams
 * //
 * User: rick
 * Date: 6/5/18
 * Time: 15:19
 */

//Field	Format
//age	slice(division, 0-2)
//division	imported
//fieldName	imported
//finish	imported
//gameId	projectId:gameNumber
//gameNumber	imported
//gameTeamId	gameId:slot
//gender	slice(division, 3)
//orgId	AYSOR:region
//orgView	Section-Area-Region
//poolKey	division-program-poolTypeView-poolSlotView
//poolSlotView	imported
//poolTeamId	projectId:poolTeamKey
//poolTeamKey	division-program-poolTypeView-poolTeamSlotView
//poolTeamSlotView	imported
//poolTeamView	age-gender program poolTypeView poolTeamSlotView
//poolTypeKey	imported
//poolTypeView	imported
//poolView	division-program poolTypeView poolSlotView
//program	imported
//regTeamId	projectId:teamKey
//regTeamName	imported
//slot	{home = 1 / away = 2}
//start	imported
//teamKey	division-program-teamNumber
//teamName	# & teamNumber & SAR & importedName
//teamNumber	assigned
//venueName	imported

//sample data
$venueName = 'Lancaster National';
$rows = [];
$rows[0] = 'Friday, July 14, 2017';
$rows[1] = 'Lancaster National - 19       (8A -- 10P)';
$rows[2] = '7250,BU10 - Core,Bracket,A1 vs A3,9A -- 9:50A,Granada Hills,Red Lions / Downey R24';
$rows[3] = 'Lancaster National - 23       (8A -- 6P)';
$rows[4] = '6635,Club Girls 03-04,Bracket,A4 vs A1,8A -- 9:10A,Valencia United,United Hawaii G03';

//private function parse row
foreach ($rows as $row) {

    //read date from row[]
    if (strtotime($row)) {
        $date = date('Y-m-d', strtotime($row));
        echo $date."\n\n";

        continue;
    }

    if (strpos($row, $venueName) > -1) {
        //read Field from row[]
        $r = preg_replace("/[^a-zA-Z0-9\s]/", '', $row);
        $data = explode(' ', $r);

        echo 'Field = '.$data[3]."\n";

        continue;
    }

//read game from row[]
    $data = explode(',', $row);
    if (count($data) > 1) {
//        var_dump($data);
        $game_num = $data[0];

        $flight = explode(' - ', $data[1]);
        if (count($flight) == 1) {
            $flight = explode(' ', $data[1]);
            $team = $data[1];
            $program = $flight[0];
            $gender = substr($flight[1], 0, 1);
            $division = $flight[2];
            $age = $flight[2];
        } else {
            $team = $flight[0].$flight[1];
            $division = $flight[0];
            $program = $flight[1];
            $gender = substr($flight[0], 0, 1);
            $age = substr($flight[0], 1, 3);
        }
        $round = $data[2];
        $poolType = null;
        switch ($round) {
            case 'Bracket':
                $poolType = 'PP';
                break;
            case 'Semi-Final':
                $poolType = 'SF';
                break;
            case'Final':
                $poolType = 'FIN';
                break;
        }

        $gameTime = explode(' -- ',$data[4]);
        $gameStart = strtotime($gameTime[0].'M');
        $gameFinish = strtotime($gameTime[1].'M');

        $game = $data[3];
        $pools = explode(' vs ', $game);

        $home = str_split($pools[0]);
        $homePool = $home[0];
        $homePoolSlot = $home[1];
        $homeTeam = $data[5];

        $away = str_split($pools[1]);
        $awayPool = $away[0];
        $awayPoolSlot = $away[1];
        $awayTeam = $data[6];


        echo "Game No. = ".$game_num."\n";
        echo "TeamKey = ".$team."\n";
        echo "Division = ".$division."\n";
        echo "Program = ".$program."\n";
        echo "Gender = ".$gender."\n";
        echo "Age = ".$age."\n";

        echo 'Start Time = '.date('H:i', $gameStart)."\n";
        echo 'End Time = '.date('H:i', $gameFinish)."\n";


        echo 'Home Team = '.$homeTeam."\n";
        echo 'Home Pool = '.$homePool."\n";
        echo 'Home Pool Slot = '.$homePoolSlot."\n";
        echo 'Home Game Slot = 1'."\n";

        echo 'Away Team = '.$awayTeam."\n";
        echo 'Away Pool = '.$awayPool."\n";
        echo 'Away PoolSlot = '.$awayPoolSlot."\n";
        echo 'Away Game Slot = 2'."\n\n";

        continue;
    }
}
