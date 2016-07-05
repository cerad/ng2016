<?php
namespace AppBundle\Action\RegPerson;

use Doctrine\DBAL\Connection;

class RegPersonFinder
{
    private $userConn;
    private $regTeamConn;
    private $regPersonConn;

    public function __construct(
        Connection $regPersonConn,
        Connection $regTeamConn,
        Connection $userConn
    ) {
        $this->userConn      = $userConn;
        $this->regTeamConn   = $regTeamConn;
        $this->regPersonConn = $regPersonConn;
    }
    /** ==========================================
     * Mainly for crews
     * projectPersonId is an autoincrement
     * This will be cleaner once the ids have been fixed up
     *
     * @param  $regPersonId string
     * @return RegPersonPerson[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegPersonPersons($regPersonId)
    {
        // Get the primary information, avoid having to make an actual primary entry for now
        $sql = <<<EOD
SELECT
  id         AS id,
  projectKey AS projectId,
  personKey  AS phyPersonId,
  name       AS name
FROM  projectPersons
WHERE projectKey = ? AND personKey = ?
EOD;
        list($projectId,$phyPersonId) = explode(':',$regPersonId);
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
        $primaryRow = $stmt->fetch();
        if (!$primaryRow) {
            return [];
        }
        $regPersonPersons[] = RegPersonPerson::createFromArray([
            'role'        => 'Primary',
            'managerId'   => $regPersonId,
            'managerName' => $primaryRow['name'],
            'memberId'    => $regPersonId,
            'memberName'  => $primaryRow['name'],
        ]);

        // Now pull the crew
        $sql = 'SELECT * FROM regPersonPersons WHERE managerId = ? ORDER BY role,memberName';
        $stmt = $this->regPersonConn->executeQuery($sql,[$regPersonId]);
        while($row = $stmt->fetch()) {
            $regPersonPersons[] = RegPersonPerson::createFromArray($row);
        }
        return $regPersonPersons;
    }
    public function findRegPersonPersonIds($regPersonId)
    {
        $sql = 'SELECT memberId FROM regPersonPersons WHERE managerId = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$regPersonId]);
        $regPersonPersonIds[$regPersonId] = $regPersonId;
        while($row = $stmt->fetch()) {
            $regPersonPersonIds[$row['memberId']] = $row['memberId'];
        }
        return $regPersonPersonIds;
    }
    /** ==========================================
     * Teams associated with RegPerson
     *
     * @param  $regPersonId string
     * @return RegPersonTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegPersonTeams($regPersonId)
    {
        $sql = 'SELECT * FROM regPersonTeams WHERE managerId = ? ORDER BY role,teamId';
        $stmt = $this->regPersonConn->executeQuery($sql,[$regPersonId]);
        $regPersonTeams = [];
        while($row = $stmt->fetch()) {
            $regPersonTeams[] = RegPersonTeam::createFromArray($row);
        }
        return $regPersonTeams;
    }
    public function findRegPersonTeamIds($regPersonId)
    {
        $sql = 'SELECT teamId FROM regPersonTeams WHERE managerId = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$regPersonId]);
        $regPersonTeamIds = [];
        while($row = $stmt->fetch()) {
            $regPersonTeamIds[$row['teamId']] = $row['teamId'];
        }
        return $regPersonTeamIds;
    }
    /* ==========================================
     * Mainly for adding people to crews
     *
     */
    public function findRegPersonChoices($projectId)
    {
        $sql = <<<EOD
SELECT 
  personKey AS personId,
  name      AS name,
  role      AS role
FROM projectPersons AS regPerson
LEFT JOIN projectPersonRoles AS regPersonRole ON regPersonRole.projectPersonId = regPerson.id
WHERE projectKey = ? AND role = 'ROLE_REFEREE'
ORDER BY name,role
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        $persons = [];
        while($row = $stmt->fetch())
        {
            $regPersonId = $projectId . ':' . $row['personId'];

            $persons[$regPersonId] = $row['name'];
        }
        return $persons;
    }
    /* ==========================================
     * For adding teams to person
     * This is a view routine, should it be in it's own class?
     * Showing program here, better to have a choice view column
     */
    public function findRegTeamChoices($projectId)
    {
        $sql = <<<EOD
SELECT regTeamId, teamName, program, gender, age
FROM regTeams AS regTeam
WHERE projectId = ?
ORDER BY regTeamId
EOD;
        $stmt = $this->regTeamConn->executeQuery($sql,[$projectId]);
        $choices = [];
        while($row = $stmt->fetch())
        {
            $choices[$row['regTeamId']] = sprintf('%s-%s %s',
                $row['age'],$row['gender'],$row['teamName']);
        }
        return $choices;
    }
    /* ==========================================
     * Mainly for Switch User within a project
     *
     */
    public function findUserChoices($projectId)
    {
        $sql = <<<EOD
SELECT 
  personKey AS personId,
  name      AS name,
  role      AS role
FROM projectPersons AS regPerson
LEFT JOIN projectPersonRoles AS regPersonRole ON regPersonRole.projectPersonId = regPerson.id
WHERE projectKey = ? AND role LIKE 'ROLE_%'
ORDER BY name,role
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        $persons = [];
        while($row = $stmt->fetch())
        {
            $personId = $row['personId'];

            if (!isset($persons[$personId])) {
                $person = [
                    'personId' => $personId,
                    'name'     => $row['name'],
                    'roles'    => $row['role'],
                ];
                $persons[$personId] = $person;
            }
            else {
                $persons[$personId]['roles'] .= ' ' . $row['role'];
            }
        }
        $sql  = 'SELECT personKey AS personId, username FROM users WHERE personKey IN (?) ORDER BY name';
        $stmt = $this->userConn->executeQuery($sql,[array_keys($persons)],[Connection::PARAM_STR_ARRAY]);
        $userChoices = [];
        while($row = $stmt->fetch()) {

            $person = $persons[$row['personId']];

            $userChoices[$row['username']] = $person['name'] . ' ' . $person['roles'];
        }
        return $userChoices;
    }
    public function isApprovedForRole($role,$regPersonId)
    {
        if (!$regPersonId) {
            return false;
        }
        list($projectId,$personId) = explode(':',$regPersonId);

        // TODO: Test some refinements here
        $sql = <<<EOD
SELECT regPersonRole.approved 
FROM projectPersons AS regPerson
LEFT JOIN projectPersonRoles AS regPersonRole ON regPersonRole.projectPersonId = regPerson.id AND regPersonRole.role = ?
WHERE regPerson.projectKey = ? AND regPerson.personKey = ?
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$role,$projectId,$personId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        return $row['approved'] ? true : false;
    }
    /* ==========================================
     * Just for the referee summary for now but
     * Also a partial design for future RegPerson
     * 
     * @param  $projectId string
     * @return RegPerson[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegPersons($projectId)
    {
        $sql = <<<EOD
SELECT
  projectKey AS projectId,
  personKey  AS personId,
  orgKey     AS orgId,
  fedKey     AS fedId,
  regYear    AS regYear,
  name,email,phone,gender,dob,age,shirtSize,notes,notesUser,avail
FROM projectPersons AS regPerson
WHERE regPerson.projectKey = ?
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        $regPersons = [];
        while($row = $stmt->fetch()) {
            $regPerson = RegPerson::createFromArray($row);
            $regPersons[$regPerson->personId] = $regPerson;
        }
        $sql = <<<EOD
SELECT 
  projectKey AS projectId,
  personKey  AS personId,
  role,badge,approved 
FROM projectPersonRoles  AS regPersonRole 
LEFT JOIN projectPersons AS regPerson ON regPerson.id = regPersonRole.projectPersonId
WHERE projectKey = ?
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        while($row = $stmt->fetch()) {
            $regPersonRole = RegPersonRole::createFromArray($row);
            $regPersons[$regPersonRole->personId]->addRole($regPersonRole);
        }
        return array_values($regPersons);
    }
}