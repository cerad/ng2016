<?php
namespace AppBundle\Action\Game;

class RegTeam
{
    public $regTeamId;
    public $projectId;

    public $teamKey;
    public $teamNumber;
    public $teamName;
    public $teamPoints;

    public $orgId;
    public $orgView;
    
    public $program;
    public $gender;
    public $age;
    public $division;
 
    public $poolKeys = [];
    public $poolTeamKeys = [];
    
    private $keys = [
        'regTeamId'  => 'RegTeamId',
        'projectId'  => 'ProjectId',
        'teamKey'    => 'string',
        'teamNumber' => 'integer',
        'teamName'   => 'string',
        'teamPoints' => 'integer',

        'orgId'   => 'OrgId',
        'orgView' => 'string',
        
        'program'  => 'string',
        'gender'   => 'string',
        'age'      => 'string',
        'division' => 'string',
    ];
    public function addPoolKey($poolKey)
    {
        $this->poolKeys[] = $poolKey;
    }
    public function addPoolTeamKey($poolTeamKey)
    {
        $this->poolTeamKeys[] = $poolTeamKey;
    }
    /**
     * @param  array $data
     * @return RegTeam
     */
    static public function createFromArray($data)
    {
        $team = new self();
        
        foreach($team->keys as $key => $type) {
            if (isset($data[$key])) {
                $team->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $team->$key = $data[$key];
            }
        }
        return $team;
    }
}
