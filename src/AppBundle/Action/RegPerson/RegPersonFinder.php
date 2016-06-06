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
    /** ==========================================
     * Teams associated with RegPerson
     *
     * @param  $regPersonId string
     * @return RegPersonTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegPersonTeams($regPersonId)
    {
        // Now pull the crew
        $sql = 'SELECT * FROM regPersonTeams WHERE managerId = ? ORDER BY role,teamId';
        $stmt = $this->regPersonConn->executeQuery($sql,[$regPersonId]);
        $regPersonTeams = [];
        while($row = $stmt->fetch()) {
            $regPersonTeams[] = RegPersonTeam::createFromArray($row);
        }
        return $regPersonTeams;
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
}