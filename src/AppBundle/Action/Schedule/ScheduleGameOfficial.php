<?php
namespace AppBundle\Action\Schedule;

/**
 *  property-read string $gameNumber
 */
class ScheduleGameOfficial
{
    public $gameOfficialId;
    public $projectId;
    public $gameId;
    public $gameNumber;
    
    public $slot;
    public $slotView = '???';
    
    public $phyPersonId;
    public $regPersonId;
    public $regPersonName;

    public $assignRole;
    public $assignState;
    
    private $keys = [

        'gameOfficialId' => 'GameOfficialId',
        'projectId'      => 'ProjectId',
        'gameId'         => 'GameId',
        'gameNumber'     => 'integer',
        'slot'           => 'integer',

        'phyPersonId'    => 'PhyPersonId',
        'regPersonId'    => 'RegPersonId',
        'regPersonName'  => 'string',
        
        'assignRole'     => 'RoleId',
        'assignState'    => 'AssignState',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('GameOfficial::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return ScheduleGameTeam
     */
    static public function createFromArray($data)
    {
        $gameOfficial = new static();

        foreach(array_keys($gameOfficial->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $gameOfficial->$key = $data[$key];
            }
        }
        switch($gameOfficial->slot) {
            case 1: $gameOfficial->slotView = 'REF'; break;
            case 2: $gameOfficial->slotView = 'AR1'; break;
            case 3: $gameOfficial->slotView = 'AR2'; break;
        }

        $gameOfficial->regPersonName = ucwords(mb_strtolower($gameOfficial->regPersonName));

        return $gameOfficial;
    }
}