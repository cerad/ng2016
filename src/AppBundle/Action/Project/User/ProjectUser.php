<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Common\ArrayAccessTrait;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class ProjectUser implements AdvancedUserInterface, \ArrayAccess, \Serializable
{
    use ArrayAccessTrait;

    // These should be all be private but phpstorm complains when they are unused
    protected $id;
    private   $name;
    protected $email;
    private   $username;

    private   $salt;
    private   $password;
    protected $passwordToken;

    private $enabled = true;
    private $locked  = false;
    
    private $roles = ['ROLE_USER'];
    
    protected $personKey;
    protected $projectKey;
    protected $registered;
    
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
    // For ng2014 code
    public function getAccountName()
    {
        return $this->name;
    }
    public function serialize()
    {
        return serialize(array(
            $this->id,         // For refreshing
            //$this->salt,
            //$this->password,
            $this->username,   // Debugging
        ));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->id,
            //$this->salt,
            //$this->password,
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
}
