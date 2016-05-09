<?php
namespace AppBundle\Action\Results2016;

class ResultsPool
{
    public $poolKey;
    public $poolTypeKey;

    public $poolView;
    public $poolTypeView;
    
    /** @var ResultsPoolTeam[]  */
    private $teams = []; // Teams in the pool
    
    /** @var ResultsGame[] */
    private $games = []; // Games in the pool

    private $keys = [
        'poolKey'      => 'PoolKey',
        'poolTypeKey'  => 'PoolTypeKey',
        'poolView'     => 'string',
        'poolTypeView' => 'PoolTypeKey',
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
            $poolTeam = $this->teams[$gameTeam->poolTeamId];
            $poolTeam->mergeGameTeam($gameTeam);
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
}