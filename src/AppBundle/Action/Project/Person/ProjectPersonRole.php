<?php
namespace AppBundle\Action\Project\Person;

use AppBundle\Common\ArrayableInterface;

class ProjectPersonRole implements ArrayableInterface,\ArrayAccess
{
    private /** @noinspection PhpUnusedPrivateFieldInspection */ $id;
    private /** @noinspection PhpUnusedPrivateFieldInspection */ $projectPersonId;

    public $role;
    public $roleDate;

    public $badge;
    public $badgeUser;
    public $badgeDate;
    public $badgeExpires;

    public $active   = true;
    public $approved = false;
    public $verified = false;
    public $ready    = true;

    public $misc;
    public $notes;

    private $scalarKeys = [
        
        'id'              => 'PrimaryKey',
        'projectPersonId' => 'ForeignKey',
        
        'role'     => 'Role', // Required
        'roleDate' => 'date',
        
        'badge'        => 'Badge', // Probably should be required?
        'badgeUser'    => 'Badge',
        'badgeDate'    => 'date',
        'badgeExpires' => 'date',
        
        'active'   => 'boolean',
        'approved' => 'boolean',
        'verified' => 'boolean',
        'ready'    => 'boolean',

        'misc'  => 'boolean',
        'notes' => 'boolean',
    ];
    private $collectionKeys = [];
    
    private $propertyKeys = [];
    
    public function __construct()
    {
        $this->propertyKeys = array_merge($this->scalarKeys,$this->collectionKeys);
    }
    public function init($role,$badge)
    {
        $this->role  = $role;
        $this->badge = $badge;
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
     * @return ProjectPersonRole
     */
    public function fromArray($data)
    {
        foreach(array_keys($this->scalarKeys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $this->$key = $data[$key];
            }
        }
        return $this; // Suppose could make it immutable
    }
    // ArrayAccess Interface
    public function offsetSet($offset, $value) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException(get_class($this) . '::set ' . $offset);
        }
        // Be fun to make this immutable
        $this->$offset = $value;
        
        return $this;
    }
    public function offsetGet($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException(get_class($this) . '::get ' . $offset);
        }
        return $this->$offset;
    }
    public function offsetExists($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException(get_class($this) . '::exists ' . $offset);
        }
        return isset($this->$offset);
    }
    public function offsetUnset($offset) {
        if (!isset($this->propertyKeys[$offset])) {
            throw new \InvalidArgumentException(get_class($this) . '::unset ' . $offset);
        }
        $this->$offset = null;
        
        return $this;
    }
}