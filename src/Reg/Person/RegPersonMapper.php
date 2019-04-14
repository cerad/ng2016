<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

/**
 * @property-read RegPersonRoleMapper $regPersonRoleMapper
 */
class RegPersonMapper
{
    public $regPersonRoleMapper;

    public function __construct(RegPersonRoleMapper $regPersonRoleMapper)
    {
        $this->regPersonRoleMapper = $regPersonRoleMapper;
    }
    // Return 2016 version of array data
    public function storeToArray2016(RegPerson $regPerson) : array
    {
        $data = [
            'id'         => $regPerson->regPersonId,
            'projectKey' => $regPerson->projectId,
            'personKey'  => $regPerson->personId,
            'orgKey'     => $regPerson->fedOrgId,
            'fedKey'     => $regPerson->fedPersonId,
            'regYear'    => $regPerson->regYear,
            'registered' => $regPerson->registered,
            'verified'   => $regPerson->verified,
            'name'       => $regPerson->name,
            'email'      => $regPerson->email,
            'phone'      => $regPerson->phone,
            'gender'     => $regPerson->gender,
            'dob'        => $regPerson->dob,
            'age'        => $regPerson->age,
            'shirtSize'  => $regPerson->shirtSize,
            'notes'      => $regPerson->notes,
            'notesUser'  => $regPerson->notesUser,
            'createdOn'  => $regPerson->createdOn,
            'version'    => $regPerson->version,
            'plans'      => $regPerson->plans,
            'avail'      => $regPerson->avail,
            'roles'      => [],
        ];
        foreach($regPerson->roles as $roleKey => $role) {

            $roleData = $this->regPersonRoleMapper->toArray2016($role);

            $data['roles'][$roleKey] = $roleData;
        }
        return $data;
    }
    public function createFromArray2016(array $data) : RegPerson
    {
        $datax = [];

        if (array_key_exists('id',$data))         $datax['regPersonId'] = (int)$data['id'];

        if (array_key_exists('projectKey',$data)) $datax['projectId']   = $data['projectKey'];
        if (array_key_exists('personKey', $data)) $datax['personId']    = $data['personKey'];
        if (array_key_exists('fedKey',    $data)) $datax['fedPersonId'] = $data['fedKey'];
        if (array_key_exists('orgKey',    $data)) $datax['fedOrgId']    = $data['orgKey'];

        if (array_key_exists('regYear',   $data)) $datax['regYear']    = $data['regYear'];
        if (array_key_exists('registered',$data)) $datax['registered'] = (bool)$data['registered'];
        if (array_key_exists('verified',  $data)) $datax['verified']   = (bool)$data['verified'];

        if (array_key_exists('name',  $data)) $datax['name']   = $data['name'];
        if (array_key_exists('email', $data)) $datax['email']  = $data['email'];
        if (array_key_exists('phone', $data)) $datax['phone']  = $data['phone'];
        if (array_key_exists('gender',$data)) $datax['gender'] = $data['gender'];
        if (array_key_exists('dob',   $data)) $datax['dob']    = $data['dob'];
        if (array_key_exists('age',   $data)) $datax['age']    = $data['age'];

        if (array_key_exists('shirtSize',$data)) $datax['shirtSize'] = $data['shirtSize'];
        if (array_key_exists('notes',    $data)) $datax['notes']     = $data['notes'];
        if (array_key_exists('notesUser',$data)) $datax['notesUser'] = $data['notesUser'];
        if (array_key_exists('createdOn',$data)) $datax['createdOn'] = $data['createdOn'];
        if (array_key_exists('updatedOn',$data)) $datax['updatedOn'] = $data['updatedOn'];
        if (array_key_exists('version',  $data)) $datax['version']   = $data['version'];

        if (array_key_exists('plans',$data)) $datax['plans'] = $data['plans'];
        if (array_key_exists('avail',$data)) $datax['avail'] = $data['avail'];

        $regPerson = new RegPerson($datax);

        if (array_key_exists('roles',$data)) {
            foreach($data['roles'] as $roleData) {
                $role = $this->regPersonRoleMapper->fromArray2016($roleData);
                $regPerson->addRole($role);
            }
        }
        return $regPerson;
    }
}