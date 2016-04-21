<?php
namespace AppBundle\Action\Project\Person;

use Doctrine\DBAL\Connection;

class ProjectPersonRepositoryV2
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    public function find($projectKey,$personKey)
    {
        $sql = 'SELECT * FROM projectPersons WHERE projectKey = ? AND personKey = ?';
        $stmt = $this->conn->executeQuery($sql,[$projectKey,$personKey]);
        
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['plans'] = isset($row['plans']) ? unserialize($row['plans']) : null;
        $row['avail'] = isset($row['avail']) ? unserialize($row['avail']) : null;
        
        $row['roles'] = [];
        $sql = 'SELECT * from projectPersonRoles WHERE projectPersonId = ?';
        $stmt = $this->conn->executeQuery($sql,[$row['id']]);
        while($roleRow = $stmt->fetch()) {
            $row['roles'][$roleRow['role']] = $roleRow;
        }
        $person = new ProjectPerson();
        return $person->fromArray($row);
    }
    public function save(ProjectPerson $person)
    {
        $row = $person->toArray();
        
        $row['plans'] = isset($row['plans']) ? serialize($row['plans']) : null;
        $row['avail'] = isset($row['avail']) ? serialize($row['avail']) : null;

        $id = $row['id']; unset($row['id']);

        $roles = $row['roles']; unset($row['roles']);

        if ($id) {
            $this->conn->update('projectPersons',$row,['id' => $id]);
            $row['id'] = $id;
        }
        else {
            // Consider it to be a trigger
            $row['name'] = $this->generateUniqueName($row['projectKey'],$row['name']);
            $this->conn->insert('projectPersons', $row);
            $row['id'] = $this->conn->lastInsertId();
        }
        $row['roles'] = [];
        foreach($roles as $roleKey => $personRole)
        {
            $personRole['projectPersonId'] = $row['id'];
            $row['roles'][$roleKey] = $this->saveRoleArray($personRole);
        }
        
        $person = new ProjectPerson();
        return $person->fromArray($row);
    }
    private function saveRoleArray($row)
    {
        $id = $row['id'];
        unset($row['id']);
        if ($id) {
            $this->conn->update('projectPersonRoles',$row,['id' => $id]);
            $row['id'] = $id;
        }
        else {
            $this->conn->insert('projectPersonRoles', $row);
            $row['id'] = $this->conn->lastInsertId();
        }
        return $row;
    }
    public function create($projectKey,$personKey,$name,$email)
    {
        $person = new ProjectPerson();

        return $person->fromArray([
            'projectKey' => $projectKey,
            'personKey'  => $personKey,
            'name'       => $name,
            'email'      => $email,
        ]);
    }
    public function createRole($role,$badge)
    {
        $personRole = new ProjectPersonRole();

        return $personRole->fromArray([
            'role'  => $role,
            'badge' => $badge,
        ]);
    }
    public function generateUniqueName($projectKey,$name)
    {
        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND name = ?';
        $stmt = $this->conn->prepare($sql);

        $cnt = 1;
        $nameTry = $name;
        while(true) {
            $stmt->execute([$projectKey,$nameTry]);
            if (!$stmt->fetch()) {
                return($nameTry);
            }
            $cnt++;
            $nameTry = sprintf('%s(%d)',$name,$cnt);
        }
        return null;
    }
}
