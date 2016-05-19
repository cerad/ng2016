<?php
namespace AppBundle\Action\Game;

class GameTeam
{
    public $gameTeamId;
    public $projectId;
    public $gameId;
    public $gameNumber;
    public $slot;

    public $regTeamId;
    public $regTeamName;
    public $division;

    public $poolTeamId;
    public $poolTeamKey;
    
    public $poolView;
    public $poolTypeView;
    public $poolTeamView;
    public $poolTeamSlotView;
    
    private $keys = [

        'gameTeamId' => 'GameTeamId',
        'projectId'  => 'ProjectId',
        'gameId'     => 'GameId',
        'gameNumber' => 'integer',
        'slot'       => 'integer',

        'regTeamId'   => 'RegTeamId',
        'regTeamName' => 'string',
        'division'    => 'string',
        
        'poolView'         => 'string',
        'poolTypeView'     => 'string',
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',

        'poolTeamId'  => 'PoolTeamId',
        'poolTeamKey' => 'PoolTeamKey',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('GameTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return GameTeam
     */
    static public function createFromArray($data)
    {
        $gameTeam = new self();
        
        foreach($gameTeam->keys as $key => $type) {
            if (isset($data[$key])) {
                $gameTeam->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $gameTeam->$key = $data[$key];
            }
        }
        return $gameTeam;
    }
}