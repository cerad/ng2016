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
/*
 *  Implements Championship bracket (Semi-final and final games) defined in National Games 2016 Governing Rules
 *  Game 5: Winner of Game 1 vs. Winner of game 2
 *  Game 6: Winner of Game 3 vs. Winner of game 4
 *  Game 7: Winners of Games 5 and 6 play for 1st and 2nd in the championship bracket
 *  Game 8: Runners-up Games 5 and 6 play for 3rd and 4th in the championship bracket
 *  Game 9: Runner-up of Game 1 vs. Runner-up of Game 2
 *  Game 10: Runner-up of Game 3 vs. Runner-up of Game 4
 *  Game 11: Winners of Games 9 and 10 play for 1st and 2nd in the consolation bracket
 *  Game 12: Runners-up of Games 9 and 10 play for 3rd and 4th in the consolation bracket
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
    public function generateFinals($sfMatches)
    {
        $fmMatches = [];
//var_dump($sfMatches);     
       
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
