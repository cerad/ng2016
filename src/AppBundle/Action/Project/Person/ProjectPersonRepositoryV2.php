<?php

namespace AppBundle\Action\Project\Person;

use AppBundle\Action\Services\VolCerts;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ProjectPersonRepositoryV2
{
    /** @var  Connection */
    private $conn;

    /** @var VolCerts */
    private $volCerts;

    public function __construct(Connection $conn, VolCerts $volCerts)
    {
        $this->conn = $conn;
        $this->volCerts = $volCerts;
    }

    /**
     * @param  $projectKey string
     * @param  $name       string|null
     * @param  $registered boolean|null
     * @param  $verified   boolean|null
     * @return ProjectPerson[]
     * @throws DBALException
     */
    public function findByProjectKey($projectKey, $name = null, $registered = null, $verified = null)
    {
        $params = [$projectKey];

        // Grab the persons, TODO use a query builder object here
        $sql = 'SELECT * FROM projectPersons WHERE projectKey = ? ';
        if ($name) {
            $params[] = '%'.$name.'%';
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

        $stmt = $this->conn->executeQuery($sql, $params);
        $personRows = [];
        $fedKeys = [];
        while ($personRow = $stmt->fetch()) {
            $personRow['plans'] = isset($personRow['plans']) ? unserialize($personRow['plans']) : null;
            $personRow['avail'] = isset($personRow['avail']) ? unserialize($personRow['avail']) : null;
            $personRow['roles'] = [];
            $personRows[$personRow['id']] = $personRow;
            if (!empty($personRow['fedKey'])) {
                $aysoid = explode(':', $personRow['fedKey'])[1];
                $fedKeys[$aysoid] = $personRow['id'];
            }
        }

        //Verify MY, SAR, Certs
        /** @var array $e3Certs */
        $certs = $this->volCerts->retrieveVolsCertData(array_keys($fedKeys));
        $e3Certs = array_combine(array_values($fedKeys), array_values($certs));
        foreach ($e3Certs as $key => $cert) {
            $ppid = $key;

            //Update MY
            $personRows[$ppid]['regYear'] = $e3Certs[$ppid]['MY'];

            //Update SAR
            $SAR = explode('/', $e3Certs[$ppid]['SAR']);
            if (count($SAR) == 3) {
                $personRows[$ppid]['orgKey'] = 'AYSOR:'.str_pad($SAR[2], 4, '0', $pad_type = STR_PAD_LEFT);
            }

            $personRows[$ppid]['verified'] = (string)true;
        }

        // Merge roles
        $personIds = array_keys($personRows);
        $sql = 'SELECT * from projectPersonRoles WHERE projectPersonId IN (?)';
        $stmt = $this->conn->executeQuery($sql, [$personIds], [Connection::PARAM_INT_ARRAY]);
        while ($roleRow = $stmt->fetch()) {
            if (isset($e3Certs[$roleRow['projectPersonId']])) {
                switch ($roleRow['role']) {
                    case 'CERT_REFEREE':
                        $roleRow['badge'] = explode(' ', $e3Certs[$roleRow['projectPersonId']]['RefCertDesc'])[0];
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['RefCertDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                    case 'CERT_SAFE_HAVEN':
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['SafeHavenDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                    case 'CERT_CONCUSSION':
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['CDCDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                }
            }

            $personRows[$roleRow['projectPersonId']]['roles'][$roleRow['role']] = $roleRow;
        }

        // Make objects
        $persons = [];
        foreach ($personRows as $personRow) {
            $person = new ProjectPerson();
            $persons[] = $person->fromArray($personRow);
        }

        return $persons;
    }

    /**
     * @param $projectKey
     * @param $personKey
     * @return ProjectPerson|null
     * @throws DBALException
     */
    public function find($projectKey, $personKey)
    {
        $sql = 'SELECT * FROM projectPersons WHERE projectKey = ? AND personKey = ?';
        $stmt = $this->conn->executeQuery($sql, [$projectKey, $personKey]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $e3Certs = [];
        $ppid = null;

        if (!empty($row['fedKey'])) {
            $aysoid = explode(':', $row['fedKey'])[1];

            //Verify MY, SAR, Certs
            /** @var array $e3Certs */
            $e3Certs[$row['id']] = $this->volCerts->retrieveVolCertData($aysoid);
            foreach ($e3Certs as $key => $cert) {
                $ppid = $key;
                //Update MY
                $row['regYear'] = $e3Certs[$ppid]['MY'];

                //Update SAR
                $SAR = explode('/', $e3Certs[$ppid]['SAR']);
                if (count($SAR) == 3) {
                    $row['orgKey'] = 'AYSOR:'.str_pad($SAR[2], 4, '0', $pad_type = STR_PAD_LEFT);
                }

                $row['verified'] = (string)true;
            }
        }

        $row['plans'] = isset($row['plans']) ? unserialize($row['plans']) : null;
        $row['avail'] = isset($row['avail']) ? unserialize($row['avail']) : null;

        $row['roles'] = [];

        $sql = 'SELECT * from projectPersonRoles WHERE projectPersonId = ?';
        $stmt = $this->conn->executeQuery($sql, [$row['id']]);

        while ($roleRow = $stmt->fetch()) {

            if (!is_null($ppid) && !empty($e3Certs)) {
                switch ($roleRow['role']) {
                    case 'CERT_REFEREE':
                        $roleRow['badge'] = explode(' ', $e3Certs[$roleRow['projectPersonId']]['RefCertDesc'])[0];
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['RefCertDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                    case 'CERT_SAFE_HAVEN':
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['SafeHavenDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                    case 'CERT_CONCUSSION':
                        $roleRow['badgeDate'] = $e3Certs[$roleRow['projectPersonId']]['CDCDate'];
                        $roleRow['verified'] = (string)true;
                        break;
                }
            }

            $row['roles'][$roleRow['role']] = $roleRow;
        }
        $person = new ProjectPerson();

        return $person->fromArray($row);
    }

    /**
     * @param ProjectPerson $person
     * @return ProjectPerson
     * @throws DBALException
     */
    public function save(ProjectPerson $person)
    {
        $row = $person->toArray();

        $row['plans'] = isset($row['plans']) ? serialize($row['plans']) : null;
        $row['avail'] = isset($row['avail']) ? serialize($row['avail']) : null;

        $id = $row['id'];
        unset($row['id']);

        $roles = $row['roles'];
        unset($row['roles']);

        if ($id) {
            $this->conn->update('projectPersons', $row, ['id' => $id]);
            $row['id'] = $id;
        } else {
            // Consider it to be a trigger
            $row['name'] = $this->generateUniqueName($row['projectKey'], $row['name']);
            $this->conn->insert('projectPersons', $row);
            $row['id'] = $this->conn->lastInsertId();
        }
        $row['roles'] = [];
        foreach ($roles as $roleKey => $personRole) {
            $personRole['projectPersonId'] = $row['id'];
            $row['roles'][$roleKey] = $this->saveRoleArray($personRole);
        }

        $person = new ProjectPerson();

        return $person->fromArray($row);
    }

    /**
     * @param $row
     * @return mixed
     * @throws DBALException
     */
    private function saveRoleArray($row)
    {
        $row['active'] = $row['active'] ? '1' : '0';
        $row['verified'] = $row['verified'] ? '1' : '0';
        $row['approved'] = $row['approved'] ? '1' : '0';

        $id = $row['id'];
        unset($row['id']);
        if ($id) {
            $this->conn->update('projectPersonRoles', $row, ['id' => $id]);
            $row['id'] = $id;
        } else {
            $this->conn->insert('projectPersonRoles', $row);
            $row['id'] = $this->conn->lastInsertId();
        }

        return $row;
    }

    /**
     * @param $projectKey
     * @param $personKey
     * @param $name
     * @param $email
     * @return ProjectPerson
     */
    public function create($projectKey, $personKey, $name, $email)
    {
        $person = new ProjectPerson();

        return $person->fromArray(
            [
                'projectKey' => $projectKey,
                'personKey' => $personKey,
                'name' => $name,
                'email' => $email,
            ]
        );
    }

    /**
     * @param $role
     * @param $badge
     * @return ProjectPersonRole
     */
    public function createRole($role, $badge)
    {
        $personRole = new ProjectPersonRole();

        return $personRole->fromArray(
            [
                'role' => $role,
                'badge' => $badge,
            ]
        );
    }

    /**
     * @param $projectKey
     * @param $name
     * @return null
     * @throws DBALException
     */
    public function generateUniqueName($projectKey, $name)
    {
        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND name = ?';
        $stmt = $this->conn->prepare($sql);

        $cnt = 1;
        $nameTry = $name;
        while (true) {
            $stmt->execute([$projectKey, $nameTry]);
            if (!$stmt->fetch()) {
                return ($nameTry);
            }
            $cnt++;
            $nameTry = sprintf('%s(%d)', $name, $cnt);
        }

        return null;
    }
}
