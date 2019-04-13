<?php
namespace Zayso\Reg\Person;

use DateTime;
use Zayso\Common\Traits\SetterTrait;

/**
 * @property-read int regPersonRoleId
 * @property-read int regPersonId
 *
 * @property-read string   role
 * @property-read DateTime roleDate
 * @property-read string   badge
 * @property-read DateTime badgeDate
 * @property-read string   badgeUser
 * @property-read DateTime badgeExpires
 *
 * @property-read bool active
 * @property-read bool approved
 * @property-read bool verified
 * @property-read bool ready
 *
 * @property-read string misc
 * @property-read string notes
 */
class RegPersonRole
{
    use SetterTrait;

    public $regPersonRoleId;  // autoinc
    public $regPersonId;
    
    public $role;
    public $roleDate;

    public $badge;
    public $badgeDate;
    public $badgeUser;
    public $badgeExpires;

    public $active   = true;
    public $approved = false;
    public $verified = false;
    public $ready    = true;

    public $misc;
    public $notes;

    public function __construct(array $data = [])
    {
        $this->setArray($data);
    }
    private function init(array $data) : void
    {
        if (isset($data['regPersonRoleId'])) $this->regPersonRoleId = (int)$data['regPersonRoleId'];
        if (isset($data['regPersonId']))     $this->regPersonId     = (int)$data['regPersonId'];

        if (isset($data['role']))     $this->role     = $data['role'];
        if (isset($data['roleDate'])) $this->roleDate = $data['roleDate'];

        if (isset($data['badge']))        $this->badge        = $data['badge'];
        if (isset($data['badgeUser']))    $this->badgeUser    = $data['badgeUser'];
        if (isset($data['badgeDate']))    $this->badgeDate    = $data['badgeDate'];
        if (isset($data['badgeExpires'])) $this->badgeExpires = $data['badgeExpires'];

        if (isset($data['active']))   $this->active   = (bool)$data['active'];
        if (isset($data['approved'])) $this->approved = (bool)$data['approved'];
        if (isset($data['verified'])) $this->verified = (bool)$data['verified'];
        if (isset($data['ready']))    $this->ready    = (bool)$data['ready'];

        if (isset($data['misc']))  $this->misc  = $data['misc'];
        if (isset($data['notes'])) $this->notes = $data['notes'];

    }
}