<?php
namespace AppBundle\Action\RegPerson;

class RegPersonPerson
{
    public $regPersonPersonId;
    
    public $role;

    public $managerId;
    public $managerName;
    
    public $memberId;
    public $memberName;
    
    private $keys = [
        'regPersonPersonId' => 'RegPersonPersonId',
        'role'              => 'RegPersonPersonRoleType',
        'managerId'         => 'RegPersonId',
        'managerName'       => 'string',
        'memberId'          => 'RegPersonId',
        'memberName'        => 'string',
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('RegPersonPerson::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return RegPersonPerson
     */
    static public function createFromArray($data)
    {
        $item = new self();
        
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