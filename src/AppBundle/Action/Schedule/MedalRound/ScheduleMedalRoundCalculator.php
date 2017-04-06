<?php
namespace AppBundle\Action\Schedule\MedalRound;

class ScheduleMedalRoundCalculator 
{
/*
 *    Implements Quarterfinal competition defined in National Games 2016 Governing Rules
 *
 *    Game 1: 1st in pool A vs. 2nd in pool C
 *    Game 2: 1st in pool B vs. 2nd in pool D
 *    Game 3: 1st in pool C vs. 2nd in pool A
 *    Game 4: 1st in pool D vs. 2nd in pool B
 */
    public function generateQuarterFinals($pools)
    {
        $qfMatches = [];
        $result = [];

        $keySet = array(
            ['U10BCorePPA','U10BCorePPB','U10BCorePPC','U10BCorePPD'],
            ['U10GCorePPA','U10GCorePPB','U10GCorePPC','U10GCorePPD'],
            ['U12BCorePPA','U12BCorePPB','U12BCorePPC','U12BCorePPD'],
            ['U12GCorePPA','U12GCorePPB','U12GCorePPC','U12GCorePPD'],
            ['U14BCorePPA','U14BCorePPB','U14BCorePPC','U14BCorePPD'],
            ['U14GCorePPA','U14GCorePPB','U14GCorePPC','U14GCorePPD'],
            ['U16BCorePPA','U16BCorePPB','U16BCorePPC','U16BCorePPD'],
            ['U16GCorePPA','U16GCorePPB','U16GCorePPC','U16GCorePPD'],
            ['U19BCorePPA','U19BCorePPB','U19BCorePPC','U19BCorePPD'],
            ['U19GCorePPA','U19GCorePPB','U19GCorePPC','U19GCorePPD'],
        );

        foreach($keySet as $keys) {
            $keyPools = array_intersect_key($pools, array_fill_keys($keys, null) );

            switch ( count($keyPools) ){
                case 1:
                    $result = $this->generateQuarterFinals1Pools($keyPools);
                    break;
                case 2:
                    $result = $this->generateQuarterFinals2Pools($keyPools);
                    break;
                case 3:
                    $result = $this->generateQuarterFinals3Pools($keyPools);
                    break;
                case 4:
                    $result = $this->generateQuarterFinals4Pools($keyPools);
                    break;
            }
                
            $qfMatches = array_merge( $qfMatches, $result );            
        }
        
        $qfResults = $this->generateScheduleRecords($pools, 'QF', $qfMatches);
        return $qfResults;
        
    }
    public function generateQuarterFinals4Pools($pools)
    {
        $qfMatches = [];

        foreach($pools as $pool){
            $standings = $pool->getPoolTeamStandings();
            $homeTeam = $standings[0];
            $awayTeam = $standings[1];
         
            $poolKey = $pool->poolKey;
            $switchKey = substr($poolKey, -3);
            $prefix = substr($poolKey, 0, 8);

            switch ($switchKey) {
                case 'PPA':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>$prefix.'QF1X');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>$prefix.'QF3Y');
                   break;
                case 'PPB':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>$prefix.'QF2X');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>$prefix.'QF4Y');
                   break;
                case 'PPC':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>$prefix.'QF3X');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>$prefix.'QF1Y');
                   break;
                case 'PPD':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>$prefix.'QF4X');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>$prefix.'QF2Y');
                   break;
            }

            for ($i = 2; $i < count($standings); $i++) {
                $team = $standings[$i];
                $qfMatches[$poolKey][$team->regTeamName] = array('Slots'=>$team->poolTeamSlotView,'QF'=>'');                
            }
        }        

        return $qfMatches;
    
    }
    public function generateQuarterFinals3Pools($pools)
    {
        $qfMatches = [];

        $team = [];
        foreach($pools as $pool){
            $poolKey = $pool->poolKey;
            $slotView = $pool->poolSlotView;
            $teams = $pool->getPoolTeamStandings();
            $teams = array_values($teams);

            foreach ($teams as $t){
                $qfMatches[$poolKey][$t->regTeamName] = array('Slots'=>$t->poolTeamSlotView,'QF'=>'');
            }

            for ($i = 0; $i < count($teams); $i++){
                $team[$slotView][$i] = $teams[$i];
            }
        }

        //compute winPercent
        $a2WinPercent = $team['A'][2]->gamesPlayed > 0 ? $team['A'][2]->pointsEarned / $team['A'][2]->gamesPlayed : null;
        $b2WinPercent = $team['B'][2]->gamesPlayed > 0 ? $team['B'][2]->pointsEarned / $team['B'][2]->gamesPlayed : null;
        $c2WinPercent = $team['C'][2]->gamesPlayed > 0 ? $team['C'][2]->pointsEarned / $team['C'][2]->gamesPlayed : null;
        
        foreach($pools as $pool){
            $poolKey = $pool->poolKey;
            $slotView = $pool->poolSlotView;

            $switchKey = substr($poolKey, -3);
            $prefix = substr($poolKey, 0, 8);
            
            switch($switchKey) {
                case 'PPA':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF1X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF4Y');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF3Y');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF1X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF3Y');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF1X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF3X');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF4Y');
                    }
                    break;
                case 'PPB':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF3X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF2Y');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF1Y');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF4X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF1Y');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF2Y');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF2X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF3Y');
                    }
                    break;
                case 'PPC':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF4X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF2X');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF2X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF3X');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF4Y');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>$prefix.'QF4X');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>$prefix.'QF2Y');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>$prefix.'QF1Y');
                    }
                    break;
            }
        }
        
        return $qfMatches;
    
    }
    public function generateQuarterFinals2Pools($pools)
    {
        $qfMatches = [];

        foreach($pools as $pool){

            $poolKey = $pool->poolKey;

            $switchKey = substr($poolKey, -3);
            $prefix = substr($poolKey, 0, 8);

            $teams = $pool->getPoolTeamStandings();
            $teams = array_values($teams);
            
            $team = [];
            switch ($switchKey) {
                case 'PPA':
                    $team[0] = ['name'=>$teams[0]->regTeamName, 'slot'=>$teams[0]->poolTeamSlotView, 'QF'=>$prefix.'QF1X'];
                    $team[1] = ['name'=>$teams[1]->regTeamName, 'slot'=>$teams[1]->poolTeamSlotView, 'QF'=>$prefix.'QF3X'];
                    $team[2] = ['name'=>$teams[2]->regTeamName, 'slot'=>$teams[2]->poolTeamSlotView, 'QF'=>$prefix.'QF2Y'];
                    $team[3] = ['name'=>$teams[3]->regTeamName, 'slot'=>$teams[3]->poolTeamSlotView, 'QF'=>$prefix.'QF4Y'];
                    break;
                case 'PPB':
                    $team[3] = ['name'=>$teams[3]->regTeamName, 'slot'=>$teams[3]->poolTeamSlotView, 'QF'=>$prefix.'QF1Y'];
                    $team[1] = ['name'=>$teams[1]->regTeamName, 'slot'=>$teams[1]->poolTeamSlotView, 'QF'=>$prefix.'QF2X'];
                    $team[2] = ['name'=>$teams[2]->regTeamName, 'slot'=>$teams[2]->poolTeamSlotView, 'QF'=>$prefix.'QF3Y'];
                    $team[0] = ['name'=>$teams[0]->regTeamName, 'slot'=>$teams[0]->poolTeamSlotView, 'QF'=>$prefix.'QF4X'];
                    break;
            }    

            for ( $i = 4; $i < count($teams); $i++ ) {
                $team[$i] = ['name' => $teams[$i]->regTeamName, 'slot'=>$teams[$i]->poolTeamSlotView,'QF'=>''];
            }
            foreach ($team as $t) {
                $qfMatches[$poolKey][$t['name']] = array('Slots'=>$t['slot'],'QF'=>$t['QF']);
            }
        }

        return $qfMatches;
    
    }
    public function generateQuarterFinals1Pools($pool)
    {
        $qfMatches = [];
        
        $pool = array_values($pool)[0];
        $poolKey = $pool->poolKey;
        $teams = $pool->getPoolTeams();

        $teams = array_values($teams);
        $strMatches = [
            'QF1X',
            'QF3X',
            'QF4X',
            'QF2X',
            'QF2Y',
            'QF4Y',
            'QF3Y',
            'QF1Y'  
        ];
        
        for ($i = 0; $i < min(count($teams), 8); $i++) {
            $qfMatches[$poolKey][$teams[$i]->regTeamName] = array('Slots'=>$teams[$i]->poolTeamSlotView,'QF'=>$strMatches[$i]);
        }

        for ($i = 8; $i < count($teams); $i++) {
            $qfMatches[$poolKey][$teams[$i]->regTeamName] = array('Slots'=>$teams[$i]->poolTeamSlotView,'QF'=>'');                
        }

        return $qfMatches;
    
    }

/*
 *  Implements Championship & consolation bracket (Semi-finas) defined in National Games 2016 Governing Rules
 *  Game 5: Winner of Game 1 vs. Winner of game 2
 *  Game 6: Winner of Game 3 vs. Winner of game 4
 *  Game 9: Runner-up of Game 1 vs. Runner-up of Game 2
 *  Game 10: Runner-up of Game 3 vs. Runner-up of Game 4
 */
    public function generateSemiFinals($qfMatches)
    {
        $sfMatches = [];
   
        foreach($qfMatches as $pool){
            $standings = $pool->getPoolTeamStandings();

            $poolKey = $pool->poolKey;

            $switchKey = substr($poolKey, -3);
            $prefix = substr($poolKey, 0, 8);

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            foreach($standings as $team) {
                switch ($switchKey) {
                    case 'QF1':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF1X');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF3X');
                        break;
                    case 'QF2':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF1Y');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF3Y');
                        break;
                    case 'QF3':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF2X');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF4X');
                        break;
                    case 'QF4':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF2Y');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>$prefix.'SF4Y');
                        break;
                }
            }
        }
 
        $sfTeams = $this->generateScheduleRecords($qfMatches, 'SF', $sfMatches);

        return $sfTeams;
    
    }
/*
 *  Implements Championship & consolation brackets (final games) defined in National Games 2016 Governing Rules
 *  Game 7: Winners of Games 5 and 6 play for 1st and 2nd in the championship bracket
 *  Game 8: Runners-up Games 5 and 6 play for 3rd and 4th in the championship bracket
 *  Game 11: Winners of Games 9 and 10 play for 1st and 2nd in the consolation bracket
 *  Game 12: Runners-up of Games 9 and 10 play for 3rd and 4th in the consolation bracket
 */
    public function generateFinals($sfMatches)
    {
        $fmMatches = [];   
       
        foreach($sfMatches as $pool){
            $standings = $pool->getPoolTeamStandings();

            $poolKey = $pool->poolKey;
            $switchKey = substr($poolKey, -3);
            $prefix = substr($poolKey, 0, 8);

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            if (!is_null($winTeam->pointsScored)) {
                foreach ($standings as $team) {
                    switch ($switchKey) {
                        case 'SF1':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF1X');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF2X');
                            break;
                        case 'SF2':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF1Y');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF2Y');
                            break;
                        case 'SF3':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF3X');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF4X');
                            break;
                        case 'SF4':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF3Y');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => $prefix . 'TF4Y');
                            break;
                    }
                }
            } else {
                foreach ($standings as $team) {
                    switch ($switchKey) {
                        case 'SF1':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            break;
                        case 'SF2':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            break;
                        case 'SF3':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            break;
                        case 'SF4':
                            $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots' => '', 'TF' => '');
                            break;
                    }
                }

            }
        }
   
        $sfTeams = $this->generateScheduleRecords($sfMatches, 'TF', $fmMatches);

        return $sfTeams;
    
    }
    
    protected function generateScheduleRecords($pools, $hdr, $medalRounds)
    {
        //set the header labels
        $data = array(
            array ('Level','Name','SfP','Slot', $hdr)
        );
        
        $division = '';
        foreach($medalRounds as $pool=>$mrTeams) {
            //set the data : game in each row
            $teams = $pools[$pool]->getPoolTeams();
            
            //blank row between levels
            $division = count($data) == 1 ? $pools[$pool]->division : $division;
            if ($pools[$pool]->division != $division) {
                $data[] = array();
                $division = $pools[$pool]->division;
            }
            foreach($teams as $team) {                
                $mr = $mrTeams[$team->regTeamName][$hdr];
                $teamLevel = explode('-',$team->poolTeamKey)[0];

                $data[] = array(
                    $teamLevel,
                    $team->regTeamName,
                    $team->sportsmanship,
                    $team->poolTeamKey,
                    $mr,
                );
            }            
        }

        $workbook['Medal Round '.$hdr]['data'] = $data;

        return $workbook;
    
    }
}
