<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

class RegPersonRoleMapper
{
    public function toArray2016(RegPersonRole $role) : array
    {
        $data = [
            'id'              => $role->regPersonRoleId,
            'projectPersonId' => $role->regPersonId,
            'role'            => $role->role,
            'roleDate'        => $role->roleDate,
            'badge'           => $role->badge,
            'badgeUser'       => $role->badgeUser,
            'badgeDate'       => $role->badgeDate,
            'badgeExpires'    => $role->badgeExpires,
            'active'          => $role->active,
            'approved'        => $role->approved,
            'verified'        => $role->verified,
            'ready'           => $role->ready,
            'misc'            => $role->misc,
            'notes'           => $role->notes,
        ];
        return $data;
    }
    public function fromArray2016(array $data) : RegPersonRole
    {
        $datax = [];

        if (array_key_exists('id',$data))              $datax['regPersonRoleId'] = (int )$data['id'];
        if (array_key_exists('projectPersonId',$data)) $datax['projectPersonId'] =       $data['projectPersonId'];
        if (array_key_exists('role',$data))            $datax['role']            =       $data['role'];
        if (array_key_exists('roleDate',$data))        $datax['roleDate']        =       $data['roleDate'];
        if (array_key_exists('badge',$data))           $datax['badge']           =       $data['badge'];
        if (array_key_exists('badgeUser',$data))       $datax['badgeUser']       =       $data['badgeUser'];
        if (array_key_exists('badgeDate',$data))       $datax['badgeDate']       =       $data['badgeDate'];
        if (array_key_exists('badgeExpires',$data))    $datax['badgeExpires']    =       $data['badgeExpires'];
        if (array_key_exists('active',$data))          $datax['active']          = (bool)$data['active'];
        if (array_key_exists('approved',$data))        $datax['approved']        = (bool)$data['approved'];
        if (array_key_exists('verified',$data))        $datax['verified']        = (bool)$data['verified'];
        if (array_key_exists('ready',$data))           $datax['ready']           = (bool)$data['ready'];
        if (array_key_exists('misc',$data))            $datax['misc']            =       $data['misc'];
        if (array_key_exists('notes',$data))           $datax['notes']           =       $data['notes'];

        return new RegPersonRole($datax);
    }
}