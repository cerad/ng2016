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
 * @property-read boolean isReferee
 * @property-read string  refereeBadge
 * @property-read string  refereeBadgeUser
 */
class RegPerson
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

        $this->setArray($data);
    }
    // Thanks to setter this should go away
    private function init(array $data) : void
    {
        if (isset($data['regPersonId'])) $this->regPersonId = (int)$data['regPersonId'];

        if (isset($data['projectId']))   $this->projectId   = $data['projectId'];
        if (isset($data['personId']))    $this->personId    = $data['personId'];
        if (isset($data['fedPersonId'])) $this->fedPersonId = $data['fedPersonId'];
        if (isset($data['fedOrgId']))    $this->fedOrgId    = $data['fedOrgId'];

        if (isset($data['regYear']))    $this->regYear    = $data['regYear'];
        if (isset($data['registered'])) $this->registered = (bool)$data['registered'];
        if (isset($data['verified']))   $this->verified   = (bool)$data['verified'];

        if (isset($data['name']))      $this->name   = $data['name'];
        if (isset($data['email']))     $this->email  = $data['email'];
        if (isset($data['phone']))     $this->phone  = $data['phone'];
        if (isset($data['gender']))    $this->gender = $data['gender'];
        if (isset($data['dob']))       $this->dob    = $data['dob'];
        if (isset($data['age']))       $this->age    = $data['age'];

        if (isset($data['shirtSize'])) $this->shirtSize = $data['shirtSize'];
        if (isset($data['notes']))     $this->notes     = $data['notes'];
        if (isset($data['notesUser'])) $this->notesUser = $data['notesUser'];
        if (isset($data['plans']))     $this->plans     = $data['plans'];
        if (isset($data['avail']))     $this->avail     = $data['avail'];

        if (isset($data['createdOn'])) $this->createdOn = $data['createdOn'];
        if (isset($data['updatedOn'])) $this->updatedOn = $data['updatedOn'];
        if (isset($data['version']))   $this->version   = (int)$data['version'];

        if (isset($data['roles'])) {
            foreach($data['roles'] as $roleData) {
                $role = new RegPersonRole($roleData);
                $this->roles[$role->role] = $role;
            }
        }
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
        $cert->setArray(['role' => $certKey, 'active' => false]);
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
        $role->setArray(['role' => $roleKey]);
        return $role;
    }

    /**
     * TODO Use RegPersonRoles
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
    public function __get($name)
    {
        switch ($name) {
            case 'isReferee': 
                return isset($this->roles['ROLE_REFEREE']) ? true : false;
            case 'refereeBadge':
                return isset($this->roles['CERT_REFEREE']) ? $this->roles['CERT_REFEREE']->badge : null;
            case 'refereeBadgeUser':
                return isset($this->roles['CERT_REFEREE']) ? $this->roles['CERT_REFEREE']->badgeUser : null;
        }
        return null;
    }

}