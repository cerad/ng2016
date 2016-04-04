<?php
namespace AppBundle\Action\Results\PoolPlay\Calculator;

class PointsCalculator
{
    protected $pointsEarnedForWin     = 6;
    protected $pointsEarnedForTie     = 3;
    protected $pointsEarnedForLoss    = 0;
    protected $pointsEarnedForShutout = 1;

    protected $pointsEarnedForGoalsMax = 3;

    protected $pointsMinusForPlayerEjection = 1;
    protected $pointsMinusForCoachEjection  = 1;
    protected $pointsMinusForBenchEjection  = 1;
    protected $pointsMinusForSpecEjection   = 1;

    /* ===============================================
     * Old interface
     * Remove after some cleanup
     */
    public function calcPointsForGameReport($gameReport)
    {
        $homeTeamReport = $gameReport['teamReports'][1];        
        $awayTeamReport = $gameReport['teamReports'][2];
        
        $gameReport['teamReports'][1] = $this->calcPointsForTeamReport($homeTeamReport,$awayTeamReport);
        $gameReport['teamReports'][2] = $this->calcPointsForTeamReport($awayTeamReport,$homeTeamReport);

        return $gameReport;
    }
    // Takes reports as input
    protected function calcPointsForTeam($team1,$team2)
    {
        $report1 = $team1['report'];
        $report2 = $team2['report'];

        // Make scores are set
        $team1Goals = $report1['goalsScored'];
        $team2Goals = $report2['goalsScored'];

        $team1['score'] = $team1Goals;

        if (($team1Goals === null) || ($team2Goals === null))
        {
            // Maybe clear
            return $team1;
        }
        $report1['goalsAllowed'] = $team2Goals;

        $pointsEarned = 0;

        if ($team1Goals  > $team2Goals) $pointsEarned += $this->pointsEarnedForWin;
        if ($team1Goals == $team2Goals) $pointsEarned += $this->pointsEarnedForTie;
        if ($team1Goals  < $team2Goals) $pointsEarned += $this->pointsEarnedForLoss;

        if ($team2Goals == 0) $pointsEarned += $this->pointsEarnedForShutout;

        // Winning team gets goal differential
        if ($team1Goals  > $team2Goals)
        {
            $goalDiff = $team1Goals  - $team2Goals;
            if ($goalDiff > $this->pointsEarnedForGoalsMax) {
                $goalDiff = $this->pointsEarnedForGoalsMax;
            }
            $pointsEarned += $goalDiff;
        }

        $pointsEarned += $report1['fudgeFactor'];

        $pointsMinus = 0;
        $pointsMinus  += ($report1['playerEjections'] * $this->pointsMinusForPlayerEjection);
        $pointsMinus  += ($report1['coachEjections']  * $this->pointsMinusForCoachEjection);
        $pointsMinus  += ($report1['benchEjections']  * $this->pointsMinusForBenchEjection);
        $pointsMinus  += ($report1['specEjections']   * $this->pointsMinusForSpecEjection);

        $pointsEarned -= $pointsMinus;
        
        $report1['pointsMinus']  = $pointsMinus;
        $report1['pointsEarned'] = $pointsEarned;

        // Totsl ejections
        $totalEjections =
            $report1['playerEjections'] +
            $report1['coachEjections']  +
            $report1['benchEjections']  +
            $report1['specEjections'];

        $report1['totalEjections'] = $totalEjections;

        $team1['report'] = $report1;

        return $team1;
    }
    public function calcPointsForTeamReport($report1,$report2)
    {
        // Make scores are set
        $team1Goals = $report1['goalsScored'];
        $team2Goals = $report2['goalsScored'];
        
        if (($team1Goals === null) || ($team2Goals === null))
        {
            // Maybe clear
            return $report1;
        }
        $report1['goalsAllowed'] = $team2Goals;

        $pointsEarned = 0;

        if ($team1Goals  > $team2Goals) $pointsEarned += $this->pointsEarnedForWin;
        if ($team1Goals == $team2Goals) $pointsEarned += $this->pointsEarnedForTie;
        if ($team1Goals  < $team2Goals) $pointsEarned += $this->pointsEarnedForLoss;

        if ($team2Goals == 0) $pointsEarned += $this->pointsEarnedForShutout;

        // Winning team gets goal differential
        if ($team1Goals  > $team2Goals)
        {
            $goalDiff = $team1Goals  - $team2Goals;
            if ($goalDiff > $this->pointsEarnedForGoalsMax) {
                $goalDiff = $this->pointsEarnedForGoalsMax;
            }
            $pointsEarned += $goalDiff;
        }

        $pointsEarned += $report1['fudgeFactor'];

        $pointsMinus = 0;
        $pointsMinus  += ($report1['playerEjections'] * $this->pointsMinusForPlayerEjection);
        $pointsMinus  += ($report1['coachEjections']  * $this->pointsMinusForCoachEjection);
        $pointsMinus  += ($report1['benchEjections']  * $this->pointsMinusForBenchEjection);
        $pointsMinus  += ($report1['specEjections']   * $this->pointsMinusForSpecEjection);

        $pointsEarned -= $pointsMinus;

        $report1['pointsMinus']  = $pointsMinus;
        $report1['pointsEarned'] = $pointsEarned;

        // Totsl ejections
        $totalEjections =
            $report1['playerEjections'] +
            $report1['coachEjections']  +
            $report1['benchEjections']  +
            $report1['specEjections'];

        $report1['totalEjections'] = $totalEjections;
        
        return $report1;
    }
}