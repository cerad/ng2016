<?php
namespace AppBundle\Action\Results2016;

class ResultsPool
{
    public $poolKey;
    public $poolTypeKey;

    public $poolView;
    public $poolSlotView;
    public $poolTypeView;
    
    public $program;  // For medal round results
    public $division;
    
    /** @var ResultsPoolTeam[]  */
    private $teams = []; // Teams in the pool
    
    /** @var ResultsGame[] */
    private $games = []; // Games in the pool

    private $keys = [
        'poolKey'      => 'PoolKey',
        'poolTypeKey'  => 'PoolTypeKey',
        'poolView'     => 'string',
        'poolSlotView' => 'string',
        'poolTypeView' => 'PoolTypeKey',
        'program'      => 'string',
        'division'     => 'string',
    ];
    public function setPoolTeams(array $poolTeams) {
        $this->teams = $poolTeams;
    }
    public function addPoolTeam(ResultsPoolTeam $poolTeam)
    {
        $this->teams[$poolTeam->poolTeamId] = $poolTeam;
    }
    public function getPoolTeams()
    {
        return $this->teams;
    }
    public function addPoolGame(ResultsGame $game)
    {
        // For the schedule display
        $this->games[] = $game;
        
        // Summarize results
        foreach($game->getTeams() as $gameTeam)
        {
            // Need this check for U10 cross pool play, VERIFY!!!
            if (isset( $this->teams[$gameTeam->poolTeamId])) {
                $poolTeam = $this->teams[$gameTeam->poolTeamId];
                $poolTeam->mergeGameTeam($gameTeam);
            }
        }
    }
    public function getGames()
    {
        return $this->games;
    }
    /**
     * @param  $data array
     * @return ResultsPool
     */
    static function createFromArray($data)
    {
        $pool = new self();

        foreach(array_keys($pool->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $pool->$key = $data[$key];
            }
        }
        return $pool;
    }
    /** ========================================================================
     * What happens if the standings calculator is embedded here?
     * @return ResultsPoolTeam[]
     */
    public function getPoolTeamStandings()
    {
        $teams = array_values($this->teams);
        usort($teams,function(ResultsPoolTeam $team1, ResultsPoolTeam $team2) {

            // Points earned
            if ($team1->winPercent > $team2->winPercent) return -1;
            if ($team1->winPercent < $team2->winPercent) return  1;

            // Head to head
            $compare = $this->compareHeadToHead($team1,$team2);
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
        foreach(array_keys($teams) as $index) {
            $teams[$index]->standing = $standing++;
        }

        return $teams;
    }
    /** =========================================
     * Always a pain
     * Does allow for the possibility that two teams play each other more than once
     *
     * @param  ResultsPoolTeam $poolTeam1
     * @param  ResultsPoolTeam $poolTeam2
     * @return int
     */
    private function compareHeadToHead(ResultsPoolTeam $poolTeam1, ResultsPoolTeam $poolTeam2)
    {
        $compare = 0;
        $poolTeamId1 = $poolTeam1->poolTeamId;
        $poolTeamId2 = $poolTeam2->poolTeamId;
        foreach($this->games as $game) {
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