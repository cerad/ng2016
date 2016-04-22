<?php
namespace AppBundle\Action\Project\Person;

use AppBundle\Common\ArrayableInterface;

class ProjectPerson implements ArrayableInterface,\ArrayAccess
{
    private /** @noinspection PhpUnusedPrivateFieldInspection */ $id;

    public $projectKey;
    public $personKey;
    public $orgKey;
    public $fedKey;
    public $regYear;

    public $registered = null;
    public $verified   = null;

    public $name;  // Unique within project
    public $email; // Required
    public $phone;
    public $gender;
    public $dob;
    public $age;
    public $shirtSize;

    public $notes;
    public $notesUser;
    public $plans = [];
    public $avail = [];

    //private $createdOn;
    //private $updatedOn;

    public $version = 0;
    
    public $roles = [];

    private $scalarKeys = [
        'id'         => 'PrimaryKey',
        'projectKey' => 'ProjectKey',
        'personKey'  => 'PersonKey',
        'orgKey'     => 'OrgKey',
        'fedKey'     => 'FedKey',
        'regYear'    => 'RegYear',

        'registered' => 'boolean',
        'verified'   => 'boolean',

        'name'       => 'Name',  // Unique within project
        'email'      => 'Email', // Not unique but value is required
        'phone'      => 'Phone',
        'gender'     => 'Gender',
        'dob'        => 'date',
        'age'        => 'integer',
        'shirtSize'  => 'ShirtSize',

        'notes'      => 'longtext',
        'notesUser'  => 'longtext',
        'plans'      => 'array',
        'avail'      => 'array',
        'version'    => 'Version',
    ];
    private $collectionKeys = [
        'roles' => 'ProjectPersonRole',
    ];
    private $propertyKeys = [];
    
    public function __construct()
    {
        $this->propertyKeys = array_merge($this->scalarKeys,$this->collectionKeys);
    }
    public function init($projectKey,$personKey,$name,$email)
    {
        $this->projectKey = $projectKey;
        $this->personKey  = $personKey;
        $this->name       = $name;
        $this->email      = $email;
    }
    public function getKey()
    {
        return sprintf('%s.%s',$this->projectKey,$this->personKey);
    }
    public function addRole(ProjectPersonRole $personRole)
    {
        $this->roles[$personRole->role] = $personRole;
        return $this;
    }
    public function hasRole($role)
    {
        return isset($this->roles[$role]) ? true : false;
    }
    public function getRole($role)
    {
        return isset($this->roles[$role]) ? $this->roles[$role] : null;
    }
    public function getRefereeBadge()
    {
        return isset($this->roles['ROLE_REFEREE']) ? $this->roles['ROLE_REFEREE']->badge : null;
    }
    // Arrayable Interface
    public function toArray()
    {
        $data = [];
        foreach(array_keys($this->scalarKeys) as $key) {
            $data[$key] = $this->$key;
        }
        foreach (array_keys($this->collectionKeys) as $key)
        {
            $collection = [];
            foreach($this->$key as $itemKey => $item) {
                /** @noinspection PhpUndefinedMethodInspection */
                $collection[$itemKey] = $item->toArray();
            }
            $data[$key] = $collection;
        }
        return $data;
    }
    /** 
     * @param array $data
     * @return ProjectPerson
     */
    public function fromArray($data)
    {
        foreach(array_keys($this->scalarKeys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $this->$key = $data[$key];
            }
        }
        // Cheat here, later could make it generic
        $dataRoles = isset($data['roles']) ? $data['roles'] : [];
        
        foreach($dataRoles as $roleKey => $dataRole) {
            $personRole = new ProjectPersonRole();
            $personRole->fromArray($dataRole);
            $this->roles[$roleKey] = $personRole;
        }
        
        //foreach(array_keys($this->collectionKeys) as $itemKey => $itemClassName) {
        //    if (isset($data[$itemKey])) {
                
        //    }
        //}
        return $this; // Suppose could make it immutable
    }
    // ArrayAccess Interface
    public function offsetSet($offset, $value) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::set ' . $offset);
        }
        // Be fun to make this immutable
        $this->$offset = $value;
        
        return $this;
    }
    public function offsetGet($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::get ' . $offset);
        }
        return $this->$offset;
    }
    public function offsetExists($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::exists ' . $offset);
        }
        return isset($this->$offset);
    }
    public function offsetUnset($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::unset ' . $offset);
        }
        $this->$offset = null;
        
        return $this;
    }
}