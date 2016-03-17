<?php
namespace Cerad\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class User implements AdvancedUserInterface, \Serializable
{
    const ROLE_DEFAULT = 'ROLE_USER'; // From FOSUserInterface
    
    protected $id;
    protected $salt;
    
    protected $email;
    protected $emailCanonical;
    protected $emailConfirmed = false;
    
    protected $username;
    protected $usernameCanonical;
    
    protected $password;
    protected $passwordHint;
    protected $passwordPlain;
        
    protected $roles = array();
    
    // Wants to be a value object
    protected $personGuid;    // 36 char string
    protected $personStatus    = 'Active';
    protected $personVerified  = 'No';
    protected $personConfirmed = false;
    
    protected $authens; // User Authentications
    
    protected $accountName;
    protected $accountEnabled     = true;  // After first created
    protected $accountLocked      = false;
    protected $accountExpired     = false;
    protected $accountExpiresAt;
    protected $credentialsExpired = false;
    protected $credentialsExpireAt;
    
    // More value objects
    protected $passwordResetToken;
    protected $passwordResetRequestedAt;
    protected $passwordResetRequestExpiresAt;
    
    protected $emailConfirmToken;
    protected $emailConfirmRequestedAt;
    protected $emailConfirmRequestExpiresAt;
    
    protected $personConfirmToken;
    protected $personConfirmRequestedAt;
    protected $personConfirmRequestExpiresAt;
       
    // These are just events
    protected $accountCreatedOn;
    protected $accountUpdatedOn;
    protected $accountLastLoginOn;
    
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        
        $this->authens = new ArrayCollection();
    }
    /* =====================================================
     * Basic account getter/setters
     */
    public function getId()                { return $this->id;                }
    public function getSalt()              { return $this->salt;              }
    public function getEmail()             { return $this->email;             }
    public function getEmailCanonical()    { return $this->emailCanonical;    }
    public function getUsername()          { return $this->username;          }
    public function getUsernameCanonical() { return $this->usernameCanonical; }
    public function getPassword()          { return $this->password;          }
    public function getPasswordHint()      { return $this->passwordHint;      }
    public function getPasswordPlain()     { return $this->passwordPlain;     }
    public function getPlainPassword()     { return $this->passwordPlain;     }

    public function setSalt             ($value) { $this->salt = $value; }

    public function setEmail            ($value)
    {
        $this->email = $value;
        $this->emailCanonical = strtolower($value);
    }
    public function setEmailCanonical   ($value) { $this->emailCanonical    = $value; }

    public function setUsername         ($value)
    {
        $this->username = $value;
        $this->usernameCanonical = strtolower($value);
    }
    public function setUsernameCanonical($value) { $this->usernameCanonical = $value; }

    public function setPassword         ($value) { $this->password          = $value; }
    public function setPasswordHint     ($value) { $this->passwordHint      = $value; }
    public function setPasswordPlain    ($value) { $this->passwordPlain     = $value; }
    public function setPlainPassword    ($value) { $this->passwordPlain     = $value; }
    
    /* =======================================================
     * My person link
     */
    public function getPersonKey()      { return $this->personGuid;     }
    public function getPersonGuid()     { return $this->personGuid;     }
    public function getPersonStatus()   { return $this->personStatus;   }
    public function getPersonVerified() { return $this->personVerified; }

    public function setPersonGuid    ($value) { $this->personGuid     = $value; }
    public function setPersonStatus  ($value) { $this->personStatus   = $value; }
    public function setPersonVerified($value) { $this->personVerified = $value; }
    
    public function eraseCredentials()
    {
        $this->passwordPlain = null;
    }
    /* ====================================================
     * Roles stuff
     * String only for now
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }
    public function getRoles()
    {
        if (in_array(self::ROLE_DEFAULT,$this->roles,true)) return $this->roles;
        
        return array_merge(array(self::ROLE_DEFAULT),$this->roles);
    }
    public function hasRole($role) 
    { 
        $roles = $this->getRoles();
        return in_array($role,$roles);
    }
    public function addRole($role)
    {
        $roles = $this->getRoles();
        if (in_array($role,$roles)) return;
        
        $this->roles[] = $role;
    }
    public function removeRole($role)
    {
        $roles = $this->getRoles();
        $this->roles = array_diff($roles, array($role));
    }
    // Account - AdvancedUserInterface
    public function getAccountName()          { return $this->accountName;     }
   
    public function isEnabled()               { return  $this->accountEnabled;     }
    public function isAccountEnabled()        { return !$this->accountEnabled;     }
    public function isAccountNonExpired()     { return !$this->accountExpired;     }
    public function isAccountNonLocked()      { return !$this->accountLocked;      }
    public function isCredentialsNonExpired() { return !$this->credentialsExpired; }
    
    public function setEnabled              ($flag) { $this->accountEnabled     = $flag; }
    public function setAccountName          ($name) { $this->accountName        = $name; }
    public function setAccountEnabled       ($flag) { $this->accountEnabled     = $flag; }
    public function setAccountNonExpired    ($flag) { $this->accountExpired     = $flag; }
    public function setAccountNonLocked     ($flag) { $this->accountLocked      = $flag; }
    public function setCredentialsNonExpired($flag) { $this->credentialsExpired = $flag; }

    /* =========================================================================
     * Serialization
     * This is called by the security manager when adding the token to the session
     * Copied the basics from FOSUserBundle
     * 
     * Need the credential stuff because authentication takes place before refresh use is called
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,         // For refreshing
            $this->salt,
            $this->password,
            $this->username,   // Debugging
        ));
/*
       return serialize(array(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
        ));
*/
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        // $data = array_merge($data, array_fill(0, 2, null));

        list(
            $this->id,
            $this->salt,
            $this->password,
            $this->username
        ) = $data;
        
        return;
/*
        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id
        ) = $data;
*/
    }
    /* =========================================================================
     * Identifiers stuff
     */
    public function getAuthens() { return $this->authens; }
    
    public function createAuthen()
    {
        return new UserAuthen();
    }
    public function addAuthen(UserAuthen $authen)
    {
        $this->authens[] = $authen;
        
        $authen->setUser($this);
    }
    /* =========================================================================
     * Password token stuff
     */
    public function getPasswordResetToken()            { return $this->passwordResetToken;            }
    public function getPasswordResetRequestedAt()      { return $this->passwordResetRequestedAt;      }
    public function getPasswordResetRequestExpiresAt() { return $this->passwordResetRequestExpiresAt; }
    
    public function setPasswordResetToken($token)
    {
        if ($token)
        {
            $now = new \DateTime();
            $expires = clone $now;
            $expires->add(new \DateInterval('P10D'));
        }
        else
        {
            $now     = null;
            $expires = null;
        }
        
        $this->passwordResetToken = $token;
        
        $this->setPasswordResetRequestedAt     ($now);
        $this->setPasswordResetRequestExpiresAt($expires);
    }
    public function setPasswordResetRequestedAt(\DateTime $date = null)
    {
        if ($date and !($date instanceOf \DateTime)) 
        {
            $msg = 'User::setPasswordResetRequestedAt only takes DateTime argument';
            throw new \InvalidArgumentException($msg);
        }
        $this->passwordResetRequestedAt = $date;
    }
    public function setPasswordResetRequestExpiresAt(\DateTime $date = null)
    {
        if ($date and !($date instanceOf \DateTime)) 
        {
            $msg = 'User::setPasswordResetExpiresAt only takes DateTime argument';
            throw new \InvalidArgumentException($msg);
        }
        $this->passwordResetRequestExpiresAt = $date;
    }
    /* ================================================
     * Cross connect
     */
    protected $person;
    public function getPerson() { return $this->person; }
    public function setPerson($person) { $this->person = $person; }
}
?>
