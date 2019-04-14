<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

use Zayso\Common\Traits\SetterTrait;

use DateTime;

/**
 * @property-read int    regPersonId
 * @property-read string projectId
 * @property-read string personId
 * @property-read string fedOrgId
 * @property-read string fedPersonId
 * @property-read string regYear
 *
 * @property-read bool registered
 * @property-read bool verified
 *
 * @property-read string name
 * @property-read string email
 * @property-read string phone
 * @property-read string gender
 *
 * @property-read DateTime dob
 * @property-read int      age
 *
 * @property-read string shirtSize
 *
 * @property-read string notes
 * @property-read string notesUser
 *
 * @property-read array plans
 * @property-read array avail
 * @property-read RegPersonRoles roles
 *
 * @property-read DateTime createdOn
 * @property-read DateTime updatedOn
 * @property-read int      version
 *
 * Virtual
 * @property-read boolean willReferee
 * @property-read boolean willVolunteer
 *
 * @property-read boolean isRegistered
 * @property-read boolean isVerified
 *
 * @property-read boolean isReferee
 * @property-read string  refereeBadge
 * @property-read string  refereeBadgeUser
 */
final class RegPerson
{
    use SetterTrait;

    public $regPersonId = 0; // autoinc database

    public $projectId;
    public $personId;
    public $fedPersonId;
    public $fedOrgId;

    public $regYear;
    public $registered = false;
    public $verified   = false;

    public $name;
    public $email;
    public $phone;
    public $gender;
    public $dob;
    public $age;
    public $shirtSize;

    public $notes;
    public $notesUser;

    public $createdOn;
    public $updatedOn;
    public $version;

    public $roles = [];
    public $avail = [];
    public $plans = [];

    public function __construct(array $data = [])
    {
        $this->roles = new RegPersonRoles();

        $this->setFromArray($data);
    }
    public function addRole(RegPersonRole $role)
    {
        $this->roles[$role->role] = $role;
    }
    public function addCert(RegPersonRole $cert)
    {
        $this->roles[$cert->role] = $cert;
    }
    public function removeRole($roleKey)
    {
        $roleKey = is_object($roleKey) ? $roleKey->role : $roleKey;

        if (isset($this->roles[$roleKey])) {
            unset($this->roles[$roleKey]);
        }
        return $this;
    }
    public function removeCert($certKey)
    {
        $certKey = is_object($certKey) ? $certKey->role : $certKey;

        if (isset($this->roles[$certKey])) {
            unset($this->roles[$certKey]);
        }
        return $this;
    }
    public function hasRole($roleKey)
    {
        return isset($this->roles[$roleKey]) ? true : false;
    }
    public function hasCert($certKey)
    {
        return isset($this->roles[$certKey]) ? true : false;
    }

    /**
     * @param  string $certKey
     * @param  bool   $create
     * @return RegPersonRole|null
     */
    public function getCert($certKey,$create = false) : ?RegPersonRole
    {
        if (isset( $this->roles[$certKey])) {
            return $this->roles[$certKey];
        }
        if (!$create) {
            return null;
        }
        $cert = new RegPersonRole();
        $cert->setFromArray(['role' => $certKey, 'active' => false]);
        return $cert;
    }
    /**
     * @param  string $roleKey
     * @param  bool   $create
     * @return RegPersonRole|null
     */
    public function getRole($roleKey,$create = false)
    {
        if (isset( $this->roles[$roleKey])) {
            return $this->roles[$roleKey];
        }
        if (!$create) {
            return null;
        }
        $role = new RegPersonRole();
        $role->setFromArray(['role' => $roleKey]);
        return $role;
    }

    /**
     * TODO Return RegPersonRoles
     * @return RegPersonRole[]
     */
    public function getRoles()
    {
        $roles = [];
        foreach($this->roles as $roleKey => $role) {
            if (substr($roleKey,0,5) === 'ROLE_') {
                $roles[$roleKey] = $role;
            }
        }
        return $roles;
    }
    /**
     * @return RegPersonRole[]
     */
    public function getCerts()
    {
        $certs = [];
        foreach($this->roles as $certKey => $cert) {
            if (substr($certKey,0,5) === 'CERT_') {
                $certs[$certKey] = $cert;
            }
        }
        return $certs;
    }
    public function __get($name)
    {
        switch ($name) {

            case 'isVerified':   return $this->verified;
            case 'isRegistered': return $this->registered;

            case 'isReferee': 
                return isset($this->roles['ROLE_REFEREE']) ? true : false;

            case 'refereeBadge':
                return isset($this->roles['CERT_REFEREE']) ? $this->roles['CERT_REFEREE']->badge : null;

            case 'refereeBadgeUser':
                return isset($this->roles['CERT_REFEREE']) ? $this->roles['CERT_REFEREE']->badgeUser : null;

            case 'willReferee':
                return strtolower($this->plans['willReferee']) !== 'no' ? true : false;

            case 'willVolunteer':
                return strtolower($this->plans['willVolunteer']) !== 'no' ? true : false;
        }
        return null;
    }
    /* =====================================================
     * Added to support view
     *
     */
    public function getKey() : string
    {
        return sprintf('%s.%s',$this->projectId,$this->personId);
    }
    public function needsCerts()
    {
        return $this->needsCertSafeHaven() OR $this->needsCertConcussion();
    }
    public function needsCertSafeHaven()
    {
        return !(bool) $this->getCert('CERT_SAFE_HAVEN')->verified;
    }
    public function needsCertConcussion()
    {
        return !(bool) $this->getCert('CERT_CONCUSSION')->verified;
    }
}