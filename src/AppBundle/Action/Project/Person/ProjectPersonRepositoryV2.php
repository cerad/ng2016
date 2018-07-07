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

    /**
     * @param  $projectKey string
     * @param  $name       string|null
     * @param  $registered boolean|null
     * @param  $verified   boolean|null
     * @return ProjectPerson[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByProjectKey($projectKey, $name = null, $registered = null, $verified = null)
    {
        $params = [$projectKey];

        // Grab the persons, TODO use a query builder object here
        $sql = 'SELECT * FROM projectPersons WHERE projectKey = ? ';
        if ($name) {
            $params[] = '%' . $name . '%';
            $sql .= ' AND name LIKE ?';
        }
        if ($registered !== null) {
            $params[] = $registered;
            $sql .= ' AND registered = ?';
        }
        if ($verified !== null) {
            $params[] = $verified;
            $sql .= ' AND verified = ?';
        }
        $sql .= ' ORDER BY name';

        $stmt = $this->conn->executeQuery($sql,$params);
        $personRows = [];
        while($personRow = $stmt->fetch()) {
            if(is_bool(strpos($personRow['name'],'test_account'))) {
                $personRow['plans'] = isset($personRow['plans']) ? unserialize($personRow['plans']) : null;
                $personRow['avail'] = isset($personRow['avail']) ? unserialize($personRow['avail']) : null;
                $personRow['roles'] = [];
                $personRows[$personRow['id']] = $personRow;
            };
        }
        // Merge roles
        $personIds = array_keys($personRows);
        $sql = 'SELECT * from projectPersonRoles WHERE projectPersonId IN (?)';
        $stmt = $this->conn->executeQuery($sql,[$personIds],[Connection::PARAM_INT_ARRAY]);
        while($roleRow = $stmt->fetch()) {
            $personRows[$roleRow['projectPersonId']]['roles'][$roleRow['role']] = $roleRow;
        }
        // Make objects
        $persons = [];
        foreach($personRows as $personRow)
        {
            $person = new ProjectPerson();
            $persons[] = $person->fromArray($personRow);
        }
        return $persons;
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
