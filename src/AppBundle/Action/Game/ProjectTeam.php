<?php
namespace AppBundle\Action\Game;

class ProjectTeam
{
    public $projectKey;
    public $projectTeamKey;

    public $name;
    public $points;

    public $orgKey;

    // Queries, resist having a ProjectProgramGenderAgeDiv entity
    public $program,$gender,$age,$div;
    
    private $keys = [
        'projectKey'     => 'ProjectKey',
        'projectTeamKey' => 'ProjectTeamKey',
        
        'name'     => 'string',
        'points'   => 'integer|null',

        'orgKey'  => 'PhysicalOrgKey',

        'program' => 'string',
        'gender'  => 'string',
        'age'     => 'string',
        'div'     => 'string',
    ];
    /** ====================================================
     * Arrayable Interface
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
        }
        return $data;
    }
    /** 
     * @param  array $data
     * @return PoolTeam
     */
    public function fromArray($data)
    {
        foreach(array_keys($this->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $this->$key = $data[$key];
            }
        }
        return $this;
    }
}