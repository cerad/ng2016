<?php
namespace AppBundle\Action\Results\PoolPlay\Calculator;

use Cerad\Bundle\ProjectBundle\ProjectFactory;

class StandingsCalculator
{
    /** @var  ProjectFactory */
    protected $projectFactory;

    protected $games;
    protected $pools; // Combo of pool teams and games

    protected $poolGames; // Current set of games for team standings

    public function __construct(ProjectFactory $projectFactory)
    {
        $this->projectFactory = $projectFactory;
    }
    /* ============================================================
     * Chops up games into pools
     *
     */
    public function generatePools(array $games)
    {
        $this->games = $games;
        $this->pools = [];

        foreach($games as $game) {
            if ($game['group_type'] != 'PP') {
                continue;
            }
            $poolKey = $game['group_key'];
            $this->processPoolGame($game,$poolKey);
        }
        $pools = $this->pools;

        ksort($pools); // By pool key

        // Sort the teams by standing within each pool
        foreach($pools as $poolKey => $pool)
        {
            // Used for head to head
            $this->poolGames = $pools[$poolKey]['games'];

            // The teamReports
            $teams = $pool['teams'];

            //sort
            usort($teams,[$this,'comparePoolTeamStandings']);

            $pools[$poolKey]['teams'] = $teams;

            // Maybe sort the games as well? Date Time Field
        }

        return $pools;
    }
    protected function processPoolGame($game,$poolKey)
    {
        $this->pools[$poolKey]['games'][$game['id']] = $game;

        $homeTeam = $game['teams'][1];
        $awayTeam = $game['teams'][2];

        $homeGameTeamReport = $homeTeam['report'];
        $awayGameTeamReport = $awayTeam['report'];

        $homePoolTeamReport = $this->getPoolTeamReport($poolKey,$homeTeam);
        $awayPoolTeamReport = $this->getPoolTeamReport($poolKey,$awayTeam);

        // Summarizes results from individual games
        $this->calcPoolTeamPoints($poolKey,$homePoolTeamReport,$homeGameTeamReport);
        $this->calcPoolTeamPoints($poolKey,$awayPoolTeamReport,$awayGameTeamReport);

    }
    protected function getPoolTeamReport($poolKey,$team)
    {
        $poolSlot = $team['group_slot'];

        // Basically have one pool team report per slot
        if (isset( $this->pools[$poolKey]['teams'][$poolSlot])) {
            return $this->pools[$poolKey]['teams'][$poolSlot];
        }
        $report = $this->projectFactory->createProjectPoolTeamReport($team);

        //
        $report['pool_key']  = $poolKey;
        $report['pool_slot'] = $poolSlot;

        // TODO Read the rules carefully, compare 2012 and 2014
        $report['pointsEarned'] += $team['points']; // Soccerfest

        return $this->pools[$poolKey]['teams'][$poolSlot] = $report;
    }
    protected $maxGoalsScoredPerGame      = 3;
    protected $maxGoalsAllowedPerGame     = 5;
    protected $maxGoalDifferentialPerGame = 3;

    protected function calcPoolTeamPoints($poolKey,$poolTeamReport,$gameTeamReport)
    {
        // Need this because the gameTeamReport only persists values that are actually set
        $gameTeamReport = array_merge($this->projectFactory->createProjectGameTeamReport(),$gameTeamReport);

        // Goal scored and allowed
        $goalsScored  = $gameTeamReport['goalsScored'];
        $goalsAllowed = $gameTeamReport['goalsAllowed'];

        $poolTeamReport['goalsScored']  += $goalsScored;
        $poolTeamReport['goalsAllowed'] += $goalsAllowed;

        // Max limits of goals
        $goalsScoredMax  = $goalsScored  > $this->maxGoalsScoredPerGame  ? $this->maxGoalsScoredPerGame  : $goalsScored;
        $goalsAllowedMax = $goalsAllowed > $this->maxGoalsAllowedPerGame ? $this->maxGoalsAllowedPerGame : $goalsAllowed;

        $poolTeamReport['goalsScoredMax']  += $goalsScoredMax;
        $poolTeamReport['goalsAllowedMax'] += $goalsAllowedMax;

        // Goal diff
        $goalDiff = $goalsScored - $goalsAllowed;

        $goalDiffMax      = $this->maxGoalDifferentialPerGame;
        $goalDiffMaxMinus = $this->maxGoalDifferentialPerGame * -1;

        $goalDiff = $goalDiff > $goalDiffMax      ? $goalDiffMax      : $goalDiff;
        $goalDiff = $goalDiff < $goalDiffMaxMinus ? $goalDiffMaxMinus : $goalDiff;

        $goalDiff = $goalDiff > $this->maxGoalDifferentialPerGame ? $this->maxGoalDifferentialPerGame : $goalDiff;

        $poolTeamReport['goalDifferential'] += $goalDiff;

        // Points
        $poolTeamReport['pointsEarned'] += $gameTeamReport['pointsEarned'];
        $poolTeamReport['pointsMinus']  += $gameTeamReport['pointsMinus'];

        // Conduct
        $poolTeamReport['playerWarnings']  += $gameTeamReport['playerWarnings'];
        $poolTeamReport['playerEjections'] += $gameTeamReport['playerEjections'];
        $poolTeamReport['coachWarnings']   += $gameTeamReport['coachWarnings'];
        $poolTeamReport['coachEjections']  += $gameTeamReport['coachEjections'];
        $poolTeamReport['benchWarnings']   += $gameTeamReport['benchWarnings'];
        $poolTeamReport['benchEjections']  += $gameTeamReport['benchEjections'];
        $poolTeamReport['specWarnings']    += $gameTeamReport['specWarnings'];
        $poolTeamReport['specEjections']   += $gameTeamReport['specEjections'];
        $poolTeamReport['sportsmanship']   += $gameTeamReport['sportsmanship'];

        // Games
        $poolTeamReport['gamesTotal'] += 1;

        if ($goalsScored !== null) {
            $poolTeamReport['gamesPlayed'] += 1;

            if ($goalsScored  > $goalsAllowed) $poolTeamReport['gamesWon']++;
            if ($goalsScored  < $goalsAllowed) $poolTeamReport['gamesLost']++;
            if ($goalsScored == $goalsAllowed) $poolTeamReport['gamesTied']++;

        }
        /* ===========================================================
         * Winning percent formula
         * NG2014
         * WP = (PoolPlayPts + SoccerfestPts) / ( NumberOfGamesPlayed × 10) + 6
         *
         * Note: This is different than 2012?
        */
        $winPercent = null;

        if ($poolTeamReport['gamesPlayed'])
        {
            // pointsEarned includes SoccerFest points
            $wpf = ($poolTeamReport['pointsEarned'] * 1.0) / (($poolTeamReport['gamesPlayed'] * 10.0) + 6);

            //$sfPoints = $poolTeamReport->getTeam()->getTeamPoints();

            //$wpf = ($poolTeamReport->getPointsEarned() + $sfPoints) / (($poolTeamReport->getGamesPlayed() * 10) + 6);

            $winPercent = sprintf('%.3f',$wpf);
        }
        $poolTeamReport['winPercent'] = $winPercent;

        // Passing the array as reference does not work so explicit update
        $poolSlot = $poolTeamReport['team']['group_slot'];
        $this->pools[$poolKey]['teams'][$poolSlot] = $poolTeamReport;
    }
    /* ========================================================
     * The tie breaking stuff
     * Inputs are pool team reports
     * As of yet we really don't have a pool team entity
     */
    protected function comparePoolTeamStandings($team1,$team2)
    {
        // Most Winning Percent
        $wp1 = $team1['winPercent'];
        $wp2 = $team2['winPercent'];
        if ($wp1 < $wp2) return  1;
        if ($wp1 > $wp2) return -1;

        // Most Points Earned
        /* Skip because wpf should do the trick
        $pe1 = $team1['pointsEarned'];
        $pe2 = $team2['pointsEarned'];
        if ($pe1 < $pe2) return  1;
        if ($pe1 > $pe2) return -1; */

        // Head to head
        //$compare = $this->compareHeadToHead($team1,$team2);
        //if ($compare) return $compare;

        // Most Sportsmanship
        $sp1 = $team1['sportsmanship'];
        $sp2 = $team2['sportsmanship'];
        if ($sp1 < $sp2) return  1;
        if ($sp1 > $sp2) return -1;

        // Fewest Goals Allowed
        $ga1 = $team1['goalsAllowed'];
        $ga2 = $team2['goalsAllowed'];
        if ($ga1 < $ga2) return -1;
        if ($ga1 > $ga2) return  1;

        // Just the key
        $key1 = $team1['team']['group_slot'];
        $key2 = $team2['team']['group_slot'];

        return strcmp($key1,$key2);
    }
    protected function compareHeadToHead($team1,$team2)
    {
        $team1Wins = 0;
        $team2Wins = 0;

        foreach($this->poolGames as $game)
        {
            // Group will be unique within a pool
            $homeTeamGroupSlot = $game['teams'][1]['groupSlot'];
            $awayTeamGroupSlot = $game['teams'][2]['groupSlot'];

            $team1GroupSlot = $team1['team']['groupSlot'];
            $team2GroupSlot = $team2['team']['groupSlot'];

            if (($homeTeamGroupSlot == $team1GroupSlot) && ($awayTeamGroupSlot == $team2GroupSlot))
            {
                $score1 = $game['teams'][1]['goalsScored'];
                $score2 = $game['teams'][2]['goalsScored'];
                if ($score1 > $score2) $team1Wins++;
                if ($score1 < $score2) $team2Wins++;
            }
            if ($homeTeamGroupSlot == $team2GroupSlot && ($awayTeamGroupSlot == $team1GroupSlot))
            {
                $score2 = $game['teams'][1]['goalsScored'];
                $score1 = $game['teams'][2]['goalsScored'];
                if ($score1 > $score2) $team1Wins++;
                if ($score1 < $score2) $team2Wins++;
            }
        }
        if ($team1Wins < $team2Wins) return  1;
        if ($team1Wins > $team2Wins) return -1;
        return 0;
    }
}