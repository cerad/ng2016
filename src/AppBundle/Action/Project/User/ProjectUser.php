<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Common\ArrayAccessTrait;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class ProjectUser implements AdvancedUserInterface, \ArrayAccess
{
    use ArrayAccessTrait;

    public $id;
    public $email;
    public $username;

    public $salt;
    public $password;
    public $passwordPlain;
    public $passwordToken;

    public $enabled = true;
    public $locked  = false;
    
    public $roles = [];

    public $name;
    public $personKey;
    
    public function getRoles()
    {
        return $this->roles;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getSalt()
    {
        return $this->salt;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function eraseCredentials()
    {
        $this->passwordPlain = null;
    }
    public function isEnabled()
    {
        return $this->enabled;
    }
    public function isAccountNonLocked()
    {
        return $this->locked ? false : true;
    }
    public function isAccountNonExpired()
    {
        return true;
    }
    public function isCredentialsNonExpired()
    {
        return true;
    }
    public function getAccountName()
    {
        return $this->name;
    }
}
