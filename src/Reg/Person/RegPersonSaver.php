<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

final class RegPersonSaver
{
    private $regPersonConn;
    private $regPersonFinder;
    private $regPersonMapper;
    private $regPersonRoleMapper;

    public function __construct(
        RegPersonConnection $regPersonConn,
        RegPersonFinder     $regPersonFinder,
        RegPersonMapper     $regPersonMapper,
        RegPersonRoleMapper $regPersonRoleMapper)
    {
        $this->regPersonConn       = $regPersonConn;
        $this->regPersonFinder     = $regPersonFinder;
        $this->regPersonMapper     = $regPersonMapper;
        $this->regPersonRoleMapper = $regPersonRoleMapper;
    }
    public function save(RegPerson $regPerson) : RegPerson
    {
        $regPersonArray = $this->regPersonMapper->storeToArray2016($regPerson);

        $regPersonArray['plans'] = isset($regPersonArray['plans']) ? serialize($regPersonArray['plans']) : null;
        $regPersonArray['avail'] = isset($regPersonArray['avail']) ? serialize($regPersonArray['avail']) : null;

        $id = $regPersonArray['id'];
        unset($regPersonArray['id']);

        unset($regPersonArray['roles']);

        if ($id) {
            $this->regPersonConn->update('projectPersons', $regPersonArray, ['id' => $id]);
        } else {
            // Consider it to be a trigger
            $regPersonArray['name'] = $this->generateUniqueName($regPersonArray['projectKey'], $regPersonArray['name']);

            $this->regPersonConn->insert('projectPersons', $regPersonArray);

            $id = $this->regPersonConn->lastInsertId();
        }
        foreach($regPerson->roles as $role)
        {
            $role->set('regPersonId',$id);

            $this->saveRole($role);
        }
        return $this->regPersonFinder->findByProjectPerson($regPerson->projectId,$regPerson->personId);
    }
    public function saveRole(RegPersonRole $role) : RegPersonRole
    {
        $regPersonRoleArray = $this->regPersonRoleMapper->toArray2016($role);

        $id = $regPersonRoleArray['id'];
        unset($regPersonRoleArray['id']);

        if ($id) {
            $this->regPersonConn->update('projectPersonRoles', $regPersonRoleArray, ['id' => $id]);
            $regPersonRoleArray['id'] = $id;
        } else {
            $this->regPersonConn->insert('projectPersonRoles', $regPersonRoleArray);
            $id = $regPersonRoleArray['id'] = $this->regPersonConn->lastInsertId();
            $role->set('regPersonRoleId',$id); // Maybe a wither
        }
        // To be consistent, should read it back but no role finder as of yet
        return $role;
    }
    public function generateUniqueName(string $projectId, string $name) : string
    {
        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND name = ?';
        $stmt = $this->regPersonConn->prepare($sql);

        $cnt = 1;
        $nameTry = $name;
        while (true) {
            $stmt->execute([$projectId, $nameTry]);
            if (!$stmt->fetch()) {
                return ($nameTry);
            }
            $cnt++;
            $nameTry = sprintf('%s(%d)', $name, $cnt);
        }
        return $name; // Never gets here
    }
}