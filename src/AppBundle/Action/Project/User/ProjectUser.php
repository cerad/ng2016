<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Common\ArrayAccessTrait;

use ArrayAccess;
use Serializable;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * @property-read int id
 *
 * @property-read string name
 * @property-read string email
 * @property-read string username
 *
 * @property-read bool isRegistered
 *
 * @property-read string projectId
 * @property-read string personId
 */
class ProjectUser implements AdvancedUserInterface, ArrayAccess, Serializable
{
    use ArrayAccessTrait;

    // These should be all be private but phpstorm complains when they are unused
    private $id;
    private $name;
    private $email;
    private $username;

    private $salt;
    private $password;
    private $passwordToken;

    private $enabled = true;
    private $locked  = false;
    
    private $roles = ['ROLE_USER'];
    
    private $personKey;
    private $projectKey;
    private $registered;
    
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
            $this->id,         // For refreshing
            $this->salt,
            $this->password,
            $this->username,   // Debugging
        ));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->id,
            $this->salt,
            $this->password,
            $this->username
            ) = $data;

        return;
    }
    public function getProjectId()
    {
        return $this->projectKey;
    }
    public function getPersonId()
    {
        return $this->personKey;
    }
    public function getPersonName()
    {
        return $this->name;
    }
    public function getRegPersonId()
    {
        return $this->projectKey . ':' . $this->personKey;
    }
    public function __get($name)
    {
        switch($name) {
            case 'userId':    return $this->id;
            case 'projectId': return $this->projectKey;
            case 'personId':  return $this->personKey;

            case 'name':     return $this->name;
            case 'email':    return $this->email;
            case 'username': return $this->username;

            case 'isRegistered': return (bool)$this->registered;
        }
        return null;
    }
}
