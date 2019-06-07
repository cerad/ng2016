<?php
namespace AppBundle\Action\Results;

/* ==============================================
 * This might be depreciated
 * Functionality has been moved to ResultsPool
 * Would still like to have different rules for different tournaments
 * Maybe a Pool factory of some sort?
 * Same issue with ResultsPoolTeam::mergeGameTeam
 */
class ResultsStandingsCalculator
{
    // Generates the standings for the pool
    public function __invoke(ResultsPool $pool) {
        
        $poolTeams = array_values($pool->getPoolTeams());

        $games = $pool->getGames();

        usort($poolTeams,function(ResultsPoolTeam $team1, ResultsPoolTeam $team2) use ($games)  {

            // Points earned
            if ($team1->winPercent > $team2->winPercent) return -1;
            if ($team1->winPercent < $team2->winPercent) return  1;

            // Head to head
            $compare = $this->compareHeadToHead($games,$team1,$team2);
            if ($compare) return $compare;

            // Total sportsmanship, need to convert to sportsmanship percentage
            if ($team1->sportsmanshipPercent > $team2->sportsmanshipPercent) return -1;
            if ($team1->sportsmanshipPercent < $team2->sportsmanshipPercent) return  1;

            // Goals scored, need to convert to percentage
            if ($team1->pointsScoredPercent > $team2->pointsScoredPercent) return -1;
            if ($team1->pointsScoredPercent < $team2->pointsScoredPercent) return  2;

            // Consistency, kftm comes next, also handy for before games start
            if ($team1->poolTeamId > $team2->poolTeamId) return  1;
            if ($team1->poolTeamId < $team2->poolTeamId) return -1;
            return 0;
        });
        // Add actual standings? Maybe the index should be the standing?
        $standing = 1;
        foreach(array_keys($poolTeams) as $index) {
            $poolTeams[$index]->standing = $standing++;
        }
        return $poolTeams;
    }

    /** =========================================
     * Always a pain
     * Does allow for the possibility that two teams play each other more than once
     *
     * @param  ResultsGame[]   $games
     * @param  ResultsPoolTeam $poolTeam1
     * @param  ResultsPoolTeam $poolTeam2
     * @return int
     */
    private function compareHeadToHead(array $games,ResultsPoolTeam $poolTeam1, ResultsPoolTeam $poolTeam2)
    {
        $compare = 0;
        $poolTeamId1 = $poolTeam1->poolTeamId;
        $poolTeamId2 = $poolTeam2->poolTeamId;
        foreach($games as $game) {
            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;
            if ($poolTeamId1 === $homeTeam->poolTeamId && $poolTeamId2 === $awayTeam->poolTeamId) {
                if ($homeTeam->results === 1) $compare--;
                if ($homeTeam->results === 2) $compare++;
            }
            if ($poolTeamId2 === $homeTeam->poolTeamId && $poolTeamId1 === $awayTeam->poolTeamId) {
                if ($homeTeam->results === 1) $compare++;
                if ($homeTeam->results === 2) $compare--;
            }
        }
        return $compare;
    }
}