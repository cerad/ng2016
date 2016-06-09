<?php
namespace AppBundle\Action\Schedule2016\MedalRound;

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
            ['U10B-Core-PP-A','U10B-Core-PP-B','U10B-Core-PP-C','U10B-Core-PP-D'],
            ['U10G-Core-PP-A','U10G-Core-PP-B','U10G-Core-PP-C','U10G-Core-PP-D'],
            ['U12B-Core-PP-A','U12B-Core-PP-B','U12B-Core-PP-C','U12B-Core-PP-D'],
            ['U12G-Core-PP-A','U12G-Core-PP-B','U12G-Core-PP-C','U12G-Core-PP-D'],
            ['U14B-Core-PP-A','U14B-Core-PP-B','U14B-Core-PP-C','U14B-Core-PP-D'],
            ['U14G-Core-PP-A','U14G-Core-PP-B','U14G-Core-PP-C','U14G-Core-PP-D'],
            ['U16B-Core-PP-A','U16B-Core-PP-B','U16B-Core-PP-C','U16B-Core-PP-D'],
            ['U16G-Core-PP-A','U16G-Core-PP-B','U16G-Core-PP-C','U16G-Core-PP-D'],
            ['U19B-Core-PP-A','U19B-Core-PP-B','U19B-Core-PP-C','U19B-Core-PP-D'],
            ['U19G-Core-PP-A','U19G-Core-PP-B','U19G-Core-PP-C','U19G-Core-PP-D'],
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

            switch ($pool->poolSlotView) {
                case 'A':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:1:Home:A 1st');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:3:Away:A 2nd');
                   break;
                case 'B':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:2:Home:B 1st');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:4:Away:B 2nd');
                   break;
                case 'C':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:3:Home:C 1st');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:1:Away:C 2nd');
                   break;
                case 'D':
                   $qfMatches[$poolKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:1:Home:A 1st');
                   $qfMatches[$poolKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:3:Away:A 2nd');
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
            $games = $pool->getGames();
            $teams = $pool->getPoolTeams();
            $slotView = $pool->poolSlotView;
    
            $teams = array_values($teams);

            foreach ($teams as $t){
                $qfMatches[$poolKey][$t->regTeamName] = array('Slots'=>$t->poolTeamSlotView,'QF'=>'');
            }

            for ($i = 0; $i < count($teams); $i++){
                $team[$slotView][$i] = $teams[$i];
            }
        }

        //compute winPercent
        $a2WinPercent = $team['A'][2]->pointsEarned / $team['A'][2]->gamesPlayed;
        $b2WinPercent = $team['B'][2]->pointsEarned / $team['B'][2]->gamesPlayed;
        $c2WinPercent = $team['C'][2]->pointsEarned / $team['C'][2]->gamesPlayed;
        
        foreach($pools as $pool){
            $poolKey = $pool->poolKey;
            $slotView = $pool->poolSlotView;
            $teams = $pool->getPoolTeams();
    
            $teams = array_values($teams);
            
            switch($slotView) {
                case 'A':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:1:Home:A 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:4:Away:A 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:3:Away:A 3rd');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:1:Home:A 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:3:Away:A 2nd');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:1:Home:A 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:3:Home:A 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:4:Away:A 3rd');
                    }
                    break;
                case 'B':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:3:Home:B 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:2:Away:B 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:1:Away:B 3rd');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:4:Home:B 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:1:Away:B 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:2:Away:B 3rd');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:2:Home:B 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:3:Away:B 2nd');
                    }
                    break;
                case 'C':
                    if ($a2WinPercent > $c2WinPercent and $b2WinPercent > $c2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:4:Home:C 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:2:Home:C 2nd');
                    } elseif ($b2WinPercent > $a2WinPercent and $c2WinPercent > $a2WinPercent) {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:2:Home:C 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:3:Home:C 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:4:Away:C 3rd');
                    } else {
                        $qfMatches[$poolKey][$team[$slotView][0]->regTeamName] = array('Slots'=>$team[$slotView][0]->poolTeamSlotView,'QF'=>'QF:4:Home:C 1st');
                        $qfMatches[$poolKey][$team[$slotView][1]->regTeamName] = array('Slots'=>$team[$slotView][1]->poolTeamSlotView,'QF'=>'QF:2:Away:C 2nd');
                        $qfMatches[$poolKey][$team[$slotView][2]->regTeamName] = array('Slots'=>$team[$slotView][2]->poolTeamSlotView,'QF'=>'QF:1:Away:C 3rd');
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
            $games = $pool->getGames();
            $teams = $pool->getPoolTeams();
            $slotView = $pool->poolSlotView;
    
            $teams = array_values($teams);
            
            $team = [];
            switch ($slotView) {
                case 'A':
                    $team[0] = ['name'=>$teams[0]->regTeamName, 'slot'=>$teams[0]->poolTeamSlotView, 'QF'=>'QF:1:Home:A 1st'];
                    $team[1] = ['name'=>$teams[1]->regTeamName, 'slot'=>$teams[1]->poolTeamSlotView, 'QF'=>'QF:3:Home:A 2nd'];
                    $team[2] = ['name'=>$teams[2]->regTeamName, 'slot'=>$teams[2]->poolTeamSlotView, 'QF'=>'QF:2:Away:A 3rd'];
                    $team[3] = ['name'=>$teams[3]->regTeamName, 'slot'=>$teams[3]->poolTeamSlotView, 'QF'=>'QF:4:Away:A 4th'];
                    break;
                case 'B':
                    $team[3] = ['name'=>$teams[3]->regTeamName, 'slot'=>$teams[3]->poolTeamSlotView, 'QF'=>'QF:1:Away:B 4th'];
                    $team[1] = ['name'=>$teams[1]->regTeamName, 'slot'=>$teams[1]->poolTeamSlotView, 'QF'=>'QF:2:Home:B 2nd'];
                    $team[2] = ['name'=>$teams[2]->regTeamName, 'slot'=>$teams[2]->poolTeamSlotView, 'QF'=>'QF:3:Away:B 3rd'];
                    $team[0] = ['name'=>$teams[0]->regTeamName, 'slot'=>$teams[0]->poolTeamSlotView, 'QF'=>'QF:4:Home:B 1st'];
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
        $games = $pool->getGames();
        $teams = $pool->getPoolTeams();

        $teams = array_values($teams);
        $strMatches = [
            'QF:1:Home:A 1st',
            'QF:3:Home:A 2nd',
            'QF:4:Home:A 3rd',
            'QF:2:Home:A 4th',
            'QF:2:Away:A 5th',
            'QF:4:Away:A 6th',
            'QF:3:Away:A 7th',
            'QF:1:Away:A 8th'  
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

            $matchKey = $pool->poolTypeView . ' ' . $pool->poolSlotView;
            $poolKey = $pool->poolKey;

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            foreach($standings as $team) {
                switch ($matchKey) {
                    case 'QF 1':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:5:'.$matchKey.' Win');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:9:'.$matchKey.' Rup');
                        break;
                    case 'QF 2':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:5:'.$matchKey.' Win');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:9:'.$matchKey.' Rup');
                        break;
                    case 'QF 3':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:6:'.$matchKey.' Win');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:10:'.$matchKey.' Rup');
                        break;
                    case 'QF 4':
                        $sfMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:6:'.$matchKey.' Win');
                        $sfMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:10:'.$matchKey.' Rup');
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
            $matchKey = $pool->poolTypeView . ' ' . $pool->poolSlotView;

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            foreach($standings as $team) {
                switch ($matchKey) {
                    case 'SF 1':
                    case 'SF 5':
                        $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:7:'.$matchKey.' Win');
                        $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:8:'.$matchKey.' Run');
                        break;
                    case 'SF 2':
                    case 'SF 6':
                        $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:7:'.$matchKey.' Win');
                        $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:8:'.$matchKey.' Run');
                        break;
                    case 'SF 3':
                    case 'SF 9':
                        $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:11:'.$matchKey.' Win');
                        $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:12:'.$matchKey.' Run');
                        break;
                    case 'SF 4':
                    case 'SF 10':
                        $fmMatches[$poolKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:11:'.$matchKey.' Win');
                        $fmMatches[$poolKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:12:'.$matchKey.' Run');
                        break;
                }
            }
        }
   
        $sfTeams = $this->generateScheduleRecords($sfMatches, 'FM', $fmMatches);

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
                $teamInfo = explode(' ',$team->regTeamName);

                $data[] = array(
                    $teamLevel,
                    $team->regTeamName,
                    $team->sportsmanship,
                    $team->poolTeamSlotView,
                    $mr,
                );
            }            
        }

        $workbook['Medal Round '.$hdr]['data'] = $data;

        return $workbook;
    
    }
}
