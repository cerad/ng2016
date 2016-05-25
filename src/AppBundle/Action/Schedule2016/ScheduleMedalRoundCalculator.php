<?php
namespace AppBundle\Action\Schedule2016;

use Doctrine\DBAL\Connection;

use AppBundle\Action\Results2016\ResultsStandingsCalculator;

class ScheduleMedalRoundCalculator 
{
    private $conn;
    
    public function __construct(Connection $conn)
    {                
        $this->conn = $conn;
    }

    protected function getTeamList($level)
    {
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_teams.levelKey         AS Level',
            'project_teams.num              AS Team',
            'project_teams.orgKey           AS Region',
            'project_teams.name             AS Name',
            'project_teams.points           AS SfP',
        ]);
        $qb->from('teams', 'project_teams');
        $qb->Where('project_teams.levelKey = :level');        
        $qb->setParameter('level', $level);
        
        $stmt = $qb->execute();
        
        //put the results into an array
        $teamList = [];        
        while($row = $stmt->fetch()) {
            $teamList[] = $row;
        }
 
        return $teamList;
        
    }

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
        $data = [];
        $result = [];
        
                    //use $pools until TODO is DONE
        $result = $this->generateQuarterFinals4Pools($pools);
        //return result until TODO is DONE
        return $result;  
        
        // TODO: 1, 2, 3 pool solutions
        $keySet = array(
            ['U10B Core PP A','U10B Core PP B','U10B Core PP C','U10B Core PP D'],
            ['U10G Core PP A','U10G Core PP B','U10G Core PP C','U10G Core PP D'],
            ['U12B Core PP A','U12B Core PP B','U12B Core PP C','U12B Core PP D'],
            ['U12G Core PP A','U12G Core PP B','U12G Core PP C','U12G Core PP D'],
            ['U14B Core PP A','U14B Core PP B','U14B Core PP C','U14B Core PP D'],
            ['U14G Core PP A','U14G Core PP B','U14G Core PP C','U14G Core PP D'],
            ['U16B Core PP A','U16B Core PP B','U16B Core PP C','U16B Core PP D'],
            ['U16G Core PP A','U16G Core PP B','U16G Core PP C','U16G Core PP D'],
            ['U19B Core PP A','U19B Core PP B','U19B Core PP C','U19B Core PP D'],
            ['U19G Core PP A','U19G Core PP B','U19G Core PP C','U19G Core PP D'],
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
                    $result = $this->generateQuarterFinals4Pools($pools);
                    break;
            }
                
            $data = array_merge( $data, $result );            
        }
        
        return $data;
        
    }
    public function generateQuarterFinals4Pools($pools)
    {
        $qfMatches = [];

        foreach($pools as $pool){
            $standings = $pool->getPoolTeamStandings();
            $homeTeam = $standings[0];
            $awayTeam = $standings[1];
         
            $teamKey = $pool->poolKey;

            switch ($pool->poolSlotView) {
                case 'A':
                   $qfMatches[$teamKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:1:A 1st');
                   $qfMatches[$teamKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:3:A 2nd');
                   break;
                case 'B':
                   $qfMatches[$teamKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:2:B 1st');
                   $qfMatches[$teamKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:4:B 2nd');
                   break;
                case 'C':
                   $qfMatches[$teamKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:3:C 1st');
                   $qfMatches[$teamKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:1:C 2nd');
                   break;
                case 'D':
                   $qfMatches[$teamKey][$homeTeam->regTeamName] = array('Slots'=>$homeTeam->poolTeamSlotView,'QF'=>'QF:1:A 1st');
                   $qfMatches[$teamKey][$awayTeam->regTeamName] = array('Slots'=>$awayTeam->poolTeamSlotView,'QF'=>'QF:3:A 2nd');
                   break;
            }

            for ($i = 2; $i < count($standings); $i++) {
                $team = $standings[$i];
                $qfMatches[$teamKey][$team->regTeamName] = array('Slots'=>$team->poolTeamSlotView,'QF'=>'');                
            }
        }        

        $qfTeams = $this->generateScheduleRecords($pools, 'QF', $qfMatches);

        return $qfTeams;
    
    }
    public function generateQuarterFinals1Pools($pool)
    {
        $qfMatches = [];
        $poolGames = array_values(array_values($pool)[0]['games']);
        $levelKey = $poolGames[0]['level_key'];
        $groupName = $poolGames[0]['group_name'];
        $slot = $poolGames[0]['group_type'].':'.$poolGames[0]['group_name'].':';

        foreach ($poolGames as $game) {
            foreach ($game['teams'] as $t) {
                $teams[$t['key']] = $t;
            }
        }
        $teams = array_values($teams);

        $strMatches = [
            'QF:1:A 1st',
            'QF:3:A 2nd',
            'QF:4:A 3rd',
            'QF:2:A 4th',
            'QF:2:A 5th',
            'QF:4:A 6th',
            'QF:3:A 7th',
            'QF:1:A 8th'  
        ];
        
        for ($i = 0; $i < min(count($teams), 8); $i++) {
            $qfMatches[$levelKey][$teams[$i]['name']] = array('Slots'=>$slot.$teams[$i]['group_slot'],'QF'=>$strMatches[$i]);
        }

        for ($i = 8; $i < count($teams); $i++) {
            $qfMatches[$levelKey][$teams[$i]['name']] = array('Slots'=>$slot.$teams[$i]['group_slot'],'QF'=>'');                
        }

        return $qfMatches;
    
    }
    public function generateQuarterFinals2Pools($pools)
    {
        $qfMatches = [];

        foreach($pools as $pool){
            $poolGames = array_values($pool['games'])[0];
            $poolTeams = $pool['teams'];
            $levelKey = $poolGames['level_key'];
            $groupName = $poolGames['group_name'];
            $slot = $poolGames['group_type'].':'.$poolGames['group_name'].':';

            switch ($groupName) {
                case 'A':
                    $homeTeam[0] = [$poolTeams[0]['team'], 'QF:1:A 1st'];
                    $awayTeam[1] = [$poolTeams[2]['team'], 'QF:2:A 3rd'];
                    $homeTeam[2] = [$poolTeams[1]['team'], 'QF:3:A 2nd'];
                    $awayTeam[3] = [$poolTeams[3]['team'], 'QF:4:A 4th'];
                    break;
                case 'B':
                    $awayTeam[0] = [$poolTeams[3]['team'], 'QF:1:B 4th'];
                    $homeTeam[1] = [$poolTeams[1]['team'], 'QF:2:B 2nd'];
                    $awayTeam[2] = [$poolTeams[2]['team'], 'QF:3:B 3rd'];
                    $homeTeam[3] = [$poolTeams[0]['team'], 'QF:4:B 1st'];
                    break;
            }

        }
        
        for ( $i = 0; $i < 4; $i++ ) {
            $qfMatches[$levelKey][$homeTeam[$i][0]['name']] = array('Slots'=>$slot.$homeTeam[$i][0]['group_slot'],'QF'=>$homeTeam[$i][1]);
            $qfMatches[$levelKey][$awayTeam[$i][0]['name']] = array('Slots'=>$slot.$awayTeam[$i][0]['group_slot'],'QF'=>$awayTeam[$i][1]);            
        }

        foreach($pools as $pool){
            $poolGames = array_values($pool['games'])[0];
            $poolTeams = $pool['teams'];
            $levelKey = $poolGames['level_key'];
            $slot = $poolGames['group_type'].':'.$poolGames['group_name'].':';

            for ($i = 4; $i < count($poolTeams); $i++) {
                $team = $poolTeams[$i]['team'];
                $qfMatches[$levelKey][$team['name']] = array('Slots'=>$slot.$team['group_slot'],'QF'=>'');                
            }
        }

        return $qfMatches;
    
    }
    public function generateQuarterFinals3Pools($pools)
    {
        $qfMatches = [];

        foreach($pools as $pool){
            $poolGames = array_values($pool['games'])[0];
            $poolTeams = $pool['teams'];
            $levelKey = $poolGames['level_key'];
            $groupName = $poolGames['group_name'];
            $slot = $poolGames['group_type'].':'.$poolGames['group_name'].':';

            switch ($groupName) {
                case 'A':
                    $team['A'][0] = $poolTeams[0];
                    $team['A'][1] = $poolTeams[1];
                    $team['A'][2] = $poolTeams[2];
                   break;
                case 'B':
                    $team['B'][0] = $poolTeams[0];
                    $team['B'][1] = $poolTeams[1];
                    $team['B'][2] = $poolTeams[2];
                   break;
                case 'C':
                    $team['C'][0] = $poolTeams[0];
                    $team['C'][1] = $poolTeams[1];
                    $team['C'][2] = $poolTeams[2];
                   break;
            }
        }

        if ($team['A'][2]['winPercent'] > $team['C'][2]['winPercent'] and $team['B'][2]['winPercent'] > $team['C'][2]['winPercent']) {
            $homeTeam[0] = [$team['A'][0], 'QF:1:A 1st'];
            $awayTeam[0] = [$team['B'][2], 'QF:1:B 3rd'];
            $homeTeam[1] = [$team['C'][1], 'QF:2:C 2nd'];
            $awayTeam[1] = [$team['B'][1], 'QF:2:B 2nd'];
            $homeTeam[2] = [$team['B'][0], 'QF:3:B 1st'];
            $awayTeam[2] = [$team['A'][2], 'QF:3:A 3rd'];
            $homeTeam[3] = [$team['C'][0], 'QF:4:C 1st'];
            $awayTeam[3] = [$team['A'][1], 'QF:4:A 2nd'];
        } elseif ($team['B'][2]['winPercent'] > $team['A'][2]['winPercent'] and $team['C'][2]['winPercent'] > $team['A'][2]['winPercent']) {
            $homeTeam[0] = [$team['A'][0], 'QF:1:A 1st'];
            $awayTeam[0] = [$team['B'][1], 'QF:1:B 2nd'];
            $homeTeam[1] = [$team['C'][0], 'QF:2:C 1st'];
            $awayTeam[1] = [$team['B'][2], 'QF:2:B 3rd'];
            $homeTeam[2] = [$team['C'][1], 'QF:3:C 2nd'];
            $awayTeam[2] = [$team['A'][1], 'QF:3:A 2nd'];
            $homeTeam[3] = [$team['B'][0], 'QF:4:B 1st'];
            $awayTeam[3] = [$team['C'][2], 'QF:4:C 3rd'];
        } else {
            $homeTeam[0] = [$team['A'][0], 'QF:1:A 1st'];
            $awayTeam[0] = [$team['C'][2], 'QF:1:C 3rd'];
            $homeTeam[1] = [$team['B'][0], 'QF:2:B 1st'];
            $awayTeam[1] = [$team['C'][1], 'QF:2:C 2nd'];
            $homeTeam[2] = [$team['A'][1], 'QF:3:A 2nd'];
            $awayTeam[2] = [$team['B'][1], 'QF:3:B 2nd'];
            $homeTeam[3] = [$team['C'][0], 'QF:4:C 1st'];
            $awayTeam[3] = [$team['A'][2], 'QF:4:A 3rd'];              
        }
                    
        for ($i = 0; $i < 4; $i++ ) {
            $qfMatches[$levelKey][$homeTeam[$i][0]['team']['name']] = array('Slots'=>$slot.$homeTeam[$i][0]['team']['group_slot'],'QF'=>$homeTeam[$i][1]);
            $qfMatches[$levelKey][$awayTeam[$i][0]['team']['name']] = array('Slots'=>$slot.$awayTeam[$i][0]['team']['group_slot'],'QF'=>$awayTeam[$i][1]);                
        }

        for ($i = 2; $i < count($poolTeams); $i++) {
            $team = $poolTeams[$i]['team'];
            $qfMatches[$levelKey][$team['name']] = array('Slots'=>$slot.$team['group_slot'],'QF'=>'');                
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

            $teamKey = $pool->poolKey;
            $teamKeyArr = explode('-',$teamKey);
            $matchKey = $teamKeyArr[2] . ' ' . $teamKeyArr[3];

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            foreach($standings as $team) {
                switch ($matchKey) {
                    case 'QF 1':
                        $sfMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:5:'.$matchKey.' Win');
                        $sfMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:9:'.$matchKey.' Rup');
                        break;
                    case 'QF 2':
                        $sfMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:5:'.$matchKey.' Win');
                        $sfMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:9:'.$matchKey.' Rup');
                        break;
                    case 'QF 3':
                        $sfMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:6:'.$matchKey.' Win');
                        $sfMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:10:'.$matchKey.' Rup');
                        break;
                    case 'QF 4':
                        $sfMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:6:'.$matchKey.' Win');
                        $sfMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','SF'=>'SF:10:'.$matchKey.' Rup');
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

            $teamKey = $pool->poolKey;
            $teamKeyArr = explode('-',$teamKey);
            $matchKey = $teamKeyArr[2] . ' ' . $teamKeyArr[3];

            $winTeam = $standings[0];
            $losTeam = $standings[1];

            foreach($standings as $team) {
                switch ($matchKey) {
                    case 'SF 1':
                    case 'SF 5':
                        $fmMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:7:'.$matchKey.' Win');
                        $fmMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:8:'.$matchKey.' Run');
                        break;
                    case 'SF 2':
                    case 'SF 6':
                        $fmMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:7:'.$matchKey.' Win');
                        $fmMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:8:'.$matchKey.' Run');
                        break;
                    case 'SF 3':
                    case 'SF 9':
                        $fmMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:11:'.$matchKey.' Win');
                        $fmMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:12:'.$matchKey.' Run');
                        break;
                    case 'SF 4':
                    case 'SF 10':
                        $fmMatches[$teamKey][$winTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:11:'.$matchKey.' Win');
                        $fmMatches[$teamKey][$losTeam->regTeamName] = array('Slots'=>'','FM'=>'FM:12:'.$matchKey.' Run');
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
//var_dump($pool);
//var_dump($pools);
//var_dump($mrTeams);
            $teams = $pools[$pool]->getPoolTeams();
            
            //blank row between levels
            $division = count($data) == 1 ? $pools[$pool]->division : $division;
            if ($pools[$pool]->division != $division) {
                $data[] = array();
                $division = $pools[$pool]->division;
            }

//var_dump($teams);            
            foreach($teams as $team) {                
//var_dump($team);            
//var_dump($mrTeams[$team->regTeamName]);
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
