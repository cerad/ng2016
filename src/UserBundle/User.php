<?php
namespace UserBundle;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class User implements AdvancedUserInterface, \Serializable
{
    public $id;
    public $name;
    public $email;
    public $username;
    public $personKey;

    private $salt;
    private $password;
    public  $passwordToken;

    private $enabled = true;
    private $locked  = false;

    // This is tricky since roles are also loaded from current project
    private $roles = ['ROLE_USER'];

    public $projectKey; // Want to get rid of this?
    public $registered;

    // Advanced user interface
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
    public function serialize()
    {
        return serialize(array(
            $this->id,         // For refreshing
            $this->username,   // Debugging
        ));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->id,
            $this->username
            ) = $data;

        return;
    }
}
