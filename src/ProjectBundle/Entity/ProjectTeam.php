<?php
namespace Cerad\Bundle\ProjectBundle\Entity;

class ProjectTeam
{
    public $key;
    public $name;
    public $levelKey;

    public function __construct($key,$name,$levelKey)
    {
        $this->key  = $key;
        $this->name = $name;
        $this->levelKey = $levelKey;
    }
}