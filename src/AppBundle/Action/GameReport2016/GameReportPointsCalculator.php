<?php
namespace AppBundle\Action\GameReport2016;

class GameReportPointsCalculator
{
    private $pointsEarnedForWin     = 6;
    private $pointsEarnedForTie     = 3;
    private $pointsEarnedForLoss    = 0;
    private $pointsEarnedForShutout = 1;

    private $pointsEarnedForGoalsMax = 3;

    private $pointsMinusForPlayerEjection = 1;
    private $pointsMinusForCoachEjection  = 1;
    private $pointsMinusForBenchEjection  = 1;
    private $pointsMinusForSpecEjection   = 1;
    
    public function __invoke(GameReport $gameReport)
    {
        $this->calcPointsForTeam($gameReport->homeTeam);
        $this->calcPointsForTeam($gameReport->awayTeam);

        return $gameReport;
    }
    private function calcPointsForTeam(GameReportTeam $report)
    {
        // Make scores are set
        if ($report->pointsScored === null) {
            return $report;
        }
        $pointsEarned = 0;

        $goalsScored  = $report->pointsScored;
        $goalsAllowed = $report->pointsAllowed;

        if ($goalsScored   > $goalsAllowed) {
            $pointsEarned += $this->pointsEarnedForWin;
            $report->results       =  1;
            $report->resultsDetail = 'Won';
        }
        if ($goalsScored   < $goalsAllowed) {
            $pointsEarned += $this->pointsEarnedForLoss;
            $report->results       =  2;
            $report->resultsDetail = 'Lost';
        }
        if ($goalsScored  == $goalsAllowed) {
            $pointsEarned += $this->pointsEarnedForTie;
            $report->results = 3;
            $report->resultsDetail = 'Tied';
        }
        if ($goalsAllowed == 0) {
            $pointsEarned += $this->pointsEarnedForShutout;
        }

        // Winning team gets goal differential
        if ($goalsScored  > $goalsAllowed)
        {
            $goalDiff = $goalsScored  - $goalsAllowed;
            if ($goalDiff > $this->pointsEarnedForGoalsMax) {
                $goalDiff = $this->pointsEarnedForGoalsMax;
            }
            $pointsEarned += $goalDiff;
        }

        //$pointsEarned += $report1['fudgeFactor'];
        
        // Misconduct
        $misconduct = $report->misconduct;
        
        $pointsDeducted = 0;
        $pointsDeducted  += ($misconduct->playerEjections * $this->pointsMinusForPlayerEjection);
        $pointsDeducted  += ($misconduct->coachEjections  * $this->pointsMinusForCoachEjection);
        $pointsDeducted  += ($misconduct->benchEjections  * $this->pointsMinusForBenchEjection);
        $pointsDeducted  += ($misconduct->specEjections   * $this->pointsMinusForSpecEjection);

        $pointsEarned -= $pointsDeducted;

        $report->pointsEarned   = $pointsEarned;
        $report->pointsDeducted = $pointsDeducted;
        
        return $report;
    }
}