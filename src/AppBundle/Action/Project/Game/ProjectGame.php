<?php
namespace AppBundle\Action\Project\Game;

use AppBundle\Common\ArrayableInterface;

class ProjectGame implements ArrayableInterface,\ArrayAccess
{
    public $id;
    public $projectKey;
    public $number;
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Published';
    public $status = 'Normal';
    public $report;
    public $version = 0;

    // role
    // link
    
    private $keys = [
        'id'         => 'PrimaryKey',
        'projectKey' => 'ProjectKey',
        'number'     => 'ProjectGameNumber',
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
        'report'     => 'ProjectGameReport',
        'version'    => 'Version',
    ];
    public function __construct($projectKey,$number)
    {
        $this->number     = $number;
        $this->projectKey = $projectKey;
    }
    // Arrayable Interface
    public function toArray()
    {
        $data = [];
        foreach(array_keys($this->keys) as $key) {
            $data[$key] = $this->$key;
        }
        return $data;
    }
    /** 
     * @param array $data
     * @return ProjectGame
     */
    public function fromArray($data)
    {
        foreach(array_keys($this->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $this->$key = $data[$key];
            }
        }
        return $this; // Suppose could make it immutable
    }
    // ArrayAccess Interface
    public function offsetSet($offset, $value) {
        if (!isset($this->keys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::set ' . $offset);
        }
        $this->$offset = $value;
    }
    public function offsetGet($offset) {
        if (!isset($this->keys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::get ' . $offset);
        }
        return $this->$offset;
    }
    public function offsetExists($offset) {
        if (!isset($this->keys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::exists ' . $offset);
        }
        return isset($this->$offset);
    }
    public function offsetUnset($offset) {
        if (!isset($this->keys[$offset])) {
            throw new \InvalidArgumentException('ProjectGame::unset ' . $offset);
        }
        $this->$offset = null;
    }
}