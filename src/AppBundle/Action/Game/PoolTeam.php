<?php
namespace AppBundle\Action\Game;

class PoolTeam
{
    public $projectKey;

    // Keys
    public $poolTeamKey;  // U10-B Core PP A1  U19G Core QF 1 A 1st
    public $poolType;     // PP, QF, SF, FM
    public $poolKey;      // U10-B Core PP A   U19G Core QF 1

    // Views
    public $poolView;     // If needed
    public $poolTypeView;
    public $poolTeamView; // A1,  A 1st, SF1 Win (unique within poolKey)
    
    // Source for advancement
    public $sourcePoolKeys; // One or more pool keys
    public $sourcePoolSlot; // Standing within the source
    
    // Queries
    public $program,$gender,$age,$div;
    
    private $keys = [
        'projectKey'   => 'ProjectKey',
        'poolKey'      => 'PoolKey',
        'poolType'     => 'PoolType',
        'poolTeamKey'  => 'PoolTeamKey',

        'poolView'     => 'string',
        'poolTypeView' => 'string',
        'poolTeamView' => 'string',
        
        'sourcePoolKeys' => 'string',
        'sourcePoolSlot' => 'string',
        
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