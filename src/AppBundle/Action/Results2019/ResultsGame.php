<?php
namespace AppBundle\Action\Results2019;
/**
 * @property-read ResultsGameTeam homeTeam
 * @property-read ResultsGameTeam awayTeam
 *
 * @property-read string dow
 * @property-read string time
 */
class ResultsGame
{
    public $gameId;
    public $projectId;
    public $gameNumber;
    public $reportState;
    public $start;
    public $fieldName;
    public $status;
    
    private $teams = [];
    
    private $keys = [
        'gameId'      => 'GameId',
        'projectId'   => 'ProjectId',
        'gameNumber'  => 'integer',
        'reportState' => 'GameReportState',
        'start'       => 'datetime',
        'fieldName'   => 'string',
        'status'      => 'GameStatus',
    ];
    public function addTeam(ResultsGameTeam $team)
    {
        $this->teams[$team->slot] = $team;
    }

    /**
     * @return ResultsGameTeam[]
     */
    public function getTeams() {
        return $this->teams;
    }
    public function __get($name)
    {
        switch($name) {

            case 'homeTeam': return $this->teams[1];
            case 'awayTeam': return $this->teams[2];

            case 'dow':
                $start = \DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('D') : '???';

            case 'time':
                $start = \DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('g:i A') : '???';
        }
        throw new \InvalidArgumentException('ResultsGame::__get ' . $name);
    }
    
    /**
     * @param  array $data
     * @return ResultsGame
     */
    static public function createFromArray($data)
    {
        $game = new self();

        foreach($game->keys as $key => $type) {
            if (isset($data[$key])) {
                $game->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $game->$key = $data[$key];
            }
        }
        return $game;
    }
}