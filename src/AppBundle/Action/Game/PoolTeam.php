<?php
namespace AppBundle\Action\Game;

/**
 * @property-read string $projectKey
 * @property-read string $poolTeamKey
 */
class PoolTeam
{
    /** @var  PoolTeamId */
    public $id;

    // Keys
    public $poolKey;      // U10-B Core PP A   U19G Core QF 1
    public $poolType;     // PP, QF, SF, FM
    //     $poolTeamKey;  // U10-B Core PP A1  U19G Core QF 1 A 1st

    // Views
    public $poolView;
    public $poolTypeView;
    public $poolTeamView;
    public $poolTeamSlotView;

    // Source for advancement
    public $sourcePoolKeys; // One or more pool keys
    public $sourcePoolSlot; // Standing within the source
    
    // Queries
    public $program,$gender,$age,$division;
    
    private $keys = [
        'poolKey'      => 'PoolKey (40)',
        'poolType'     => 'PoolType(20)',

        'poolView'         => 'string(40)',
        'poolTypeView'     => 'string(40)',
        'poolTeamView'     => 'string(40)',
        'poolTeamSlotView' => 'string(40)',

        'sourcePoolKeys' => 'string(255)',
        'sourcePoolSlot' => 'integer',
        
        'program'  => 'string(20)',
        'gender'   => 'string(20)',
        'age'      => 'string(20)',
        'division' => 'string(20)',
    ];
    public function __construct($projectKey,$poolTeamKey, $poolKey = null, $poolType = null)
    {
        $this->id = new PoolTeamId($projectKey,$poolTeamKey);

        $this->poolKey  = $poolKey;
        $this->poolType = $poolType;
    }
    public function __get($name)
    {
        switch($name) {

            case 'projectKey':
                return $this->id->projectKey;

            case 'poolTeamKey':
                return $this->id->poolTeamKey;
        }
        throw new \InvalidArgumentException('PoolTeam::__get ' . $name);
    }

    /** ====================================================
     * Arrayable Interface
     * @return array
     */
    public function toArray()
    {
        $data = [
            'id'          => $this->id,
            'projectKey'  => $this->id->projectKey,
            'poolTeamKey' => $this->id->poolTeamKey,
        ];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
        }
        return $data;
    }
    /**
     * @param  array $data
     * @return PoolTeam
     */
    static public function fromArray($data)
    {
        $poolTeam = new PoolTeam($data['projectKey'],$data['poolTeamKey']);

        foreach(array_keys($poolTeam->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $poolTeam->$key = $data[$key];
            }
        }
        return $poolTeam;
    }
}