<?php
/* =========================================================
 * Focuses on calculating pool play results
 */
namespace Cerad\Bundle\GameBundle\Service\Results;

class NatGamesResults extends AbstractResults
{
    protected $pointsEarnedForWin     = 6;
    protected $pointsEarnedForTie     = 3;
    protected $pointsEarnedForLoss    = 0;
    protected $pointsEarnedForShutout = 1;
    
    protected $pointsEarnedForGoalsMax = 3;
    
    protected $pointsMinusForPlayerEjection = 1;
    protected $pointsMinusForCoachEjection  = 1;
    protected $pointsMinusForBenchEjection  = 1;
      
    public function calcPointsEarnedForTeam($team1,$team2)
    {   
        // Make scores are set
        $team1Goals = $team1->getGoalsScored();
        $team2Goals = $team2->getGoalsScored();
        if (($team1Goals === null) || ($team2Goals === null)) 
        {
            $team1->clear();
            $team2->clear();
            return;
        }
        $team1->setGoalsAllowed($team2Goals);
        $team2->setGoalsAllowed($team1Goals);
   
        $pointsEarned = 0;
        
        if ($team1Goals  > $team2Goals) $pointsEarned += $this->pointsEarnedForWin;
        if ($team1Goals == $team2Goals) $pointsEarned += $this->pointsEarnedForTie;
        if ($team1Goals  < $team2Goals) $pointsEarned += $this->pointsEarnedForLoss;
        
        if ($team2Goals == 0) $pointsEarned += $this->pointsEarnedForShutout;
        
        // Winning team gets goal differential
        if ($team1Goals  > $team2Goals)
        {
            $goalDiff = $team1Goals  - $team2Goals;
            if ($goalDiff > $this->pointsEarnedForGoalsMax) $goalDiff = $this->pointsEarnedForGoalsMax;
            $pointsEarned += $goalDiff;
        }
      
        $fudgeFactor   = $team1->getFudgeFactor();
        $pointsEarned += $fudgeFactor;
        
        $pointsMinus = 0;
        $pointsMinus  += ($team1->getPlayerEjections()* $this->pointsMinusForPlayerEjection);
        $pointsMinus  += ($team1->getCoachEjections() * $this->pointsMinusForCoachEjection);
        $pointsMinus  += ($team1->getBenchEjections() * $this->pointsMinusForBenchEjection);
             
        $pointsEarned -= $pointsMinus;
        
        // Save
        $team1->setPointsMinus ($pointsMinus);
        $team1->setPointsEarned($pointsEarned); // Just as an error check
        
        return;     
    }
    /* =====================================================
     * Standings sort based on PoolTeamReports
     */
    protected function compareTeamStandings($team1,$team2)
    {   
        // WP
        $wp1 = $team1->getWinPercent();
        $wp2 = $team2->getWinPercent();
        if ($wp1 < $wp2) return  1;
        if ($wp1 > $wp2) return -1;
        
        // Points earned
        /*
        $pe1 = $team1->getPointsEarned();
        $pe2 = $team2->getPointsEarned();
        if ($pe1 < $pe2) return  1;
        if ($pe1 > $pe2) return -1; */
        
        // Head to head
        $compare = $this->compareHeadToHead($team1,$team2);
        if ($compare) return $compare;
        
        // Sportsmanship
        $sp1 = $team1->getSportsmanship();
        $sp2 = $team2->getSportsmanship();
        if ($sp1 < $sp2) return  1;
        if ($sp1 > $sp2) return -1;
         
        // Goals Allowed
        $ga1 = $team1->getGoalsAllowed();
        $ga2 = $team2->getGoalsAllowed();
        if ($ga1 < $ga2) return -1;
        if ($ga1 > $ga2) return  1;
                
        // Just the key
        $key1 = $team1->getTeam()->getGroupSlot();
        $key2 = $team2->getTeam()->getGroupSlot();
        
        return strcmp($key1,$key2);
    }
}
?>
