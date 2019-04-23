<?php declare(strict_types=1);

namespace Zayso\User;

use Serializable;
use Zayso\Common\Contract\UserInterface;

class User implements UserInterface, Serializable
{
    // Keep private for now
    private $userId;
    private $name;
    private $email;
    private $username;

    private $salt;
    private $password;
    private $passwordToken;

    private $enabled = true;
    private $locked  = false;
    
    private $roles = ['ROLE_USER'];

    // Update this stuff later, make the project and registered go away
    private $personId;
    private $projectId;
    private $registered;

    public function __construct(
        ?int    $userId,
         string $name,
         string $username,
         string $personId,
         string $email,
        ?string $salt,
         string $password,
         bool   $enabled,
         bool   $locked,
         array  $roles
    ) {
        $this->userId   = $userId;
        $this->name     = $name;
        $this->username = $username;
        $this->personId = $personId;
        $this->email    = $email;
        $this->salt     = $salt;
        $this->password = $password;
        $this->enabled  = $enabled;
        $this->locked   = $locked;
        $this->roles    = $roles;
    }
    public function getRoles()
    {
        return $this->roles;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getPasswordToken()
    {
        return $this->passwordToken;
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
    // For noc2018 code
    public function getAccountName()
    {
        return $this->name;
    }
    public function serialize()
    {
        return serialize(array(
            $this->userId,         // For refreshing
            $this->salt,
            $this->password,
            $this->username,   // Debugging
        ));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->userId,
            $this->salt,
            $this->password,
            $this->username
            ) = $data;

        return;
    }
    public function getProjectId()
    {
        return $this->projectId;
    }
    public function getPersonId()
    {
        return $this->personId;
    }
    public function getPersonName()
    {
        return $this->name;
    }
    public function getRegPersonId() // Make go away or use key
    {
        return $this->projectId . ':' . $this->personId;
    }
    public function __get($name)
    {
        switch($name) {

            case 'userId': return $this->userId;

            case 'projectId': return $this->projectId;
            case 'personId':  return $this->personId;

            case 'name':     return $this->name;
            case 'email':    return $this->email;
            case 'username': return $this->username;

            case 'isEnabled':    return (bool)$this->enabled;
            case 'isLocked':     return (bool)$this->locked;
            case 'isRegistered': return (bool)$this->registered;

            case 'roles':     return $this->roles;
        }
        return null;
    }
}
