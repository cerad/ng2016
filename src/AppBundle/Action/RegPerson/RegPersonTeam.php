<?php
namespace AppBundle\Action\RegPerson;

class RegPersonTeam
{
    public $role = 'Family'; // Not used

    public $managerId;

    public $teamId;
    public $teamName;
    
    private $keys = [
        'role'      => 'RegPersonTeamRole',
        'managerId' => 'RegPersonId',
        'teamId'    => 'RegTeamId',
        'teamName'  => 'string',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('RegPersonTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return RegPersonTeam
     */
    static public function createFromArray($data)
    {
        $item = new self();
        
        // Todo: Move to trait if permissions work out
        foreach($item->keys as $key => $type) {
            if (isset($data[$key])) {
                $item->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $item->$key = $data[$key];
            }
        }
        return $item;
    }
}