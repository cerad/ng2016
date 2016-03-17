<?php
namespace Cerad\Bundle\UserBundle\Entity;

class UserAuthen
{   
    protected $id;      // Unique oauth string
    protected $user;
    
    protected $source;  // aka provider name
    protected $profile;
   
    protected $status = 'Active';
    
    public function setId       ($value) { $this->id        = $value; }
    public function setUser     ($value) { $this->user      = $value; }
    public function setSource   ($value) { $this->source    = $value; }
    public function setStatus   ($value) { $this->status    = $value; }
    public function setProfile  ($value) { $this->profile   = $value; }
    
    public function getId()        { return $this->id;        }
    public function getUser()      { return $this->user;      }
    public function getSource()    { return $this->source;    }
    public function getStatus()    { return $this->status;    }
    public function getProfile()   { return $this->profile;   }
    
    public function __construct()
    {
    }
}
?>