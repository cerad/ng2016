<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final class RegPersonFinder
{
    private $regPersonConn;

    public function __construct(RegPersonConnection $regPersonConn)
    {
        $this->regPersonConn = $regPersonConn;
    }
    // Maybe should be in the mapper?
    private function createRegPersonQueryBuilder() : QueryBuilder
    {
        $qb = $this->regPersonConn->createQueryBuilder();

        $qb->addSelect([
            'id         AS regPersonId',
            'projectKey AS projectId',
            'personKey  AS personId',
            'orgKey     AS fedOrgId',
            'fedKey     AS fedPersonId',
            'regYear    AS regYear',
            'registered AS registered',
            'verified   AS verified',
            'name       AS name',
            'email      AS email',
            'phone      AS phone',
            'gender     AS gender',
            'dob        AS dob',
            'age        AS age',
            'shirtSize  AS shirtSize',
            'notes      AS notes',
            'notesUser  AS notesUser',
            'plans      AS plans',
            'avail      AS avail',
            'createdOn  AS createdOn',
            'updatedOn  AS updatedOn',
            'version    AS version'
        ]);
        $qb->from('projectPersons', 'regPerson');

        return $qb;
    }
    private function processRegPersonRows(array $rows) : RegPersons
    {
        $regPersons = new RegPersons();
        $regPersonIds = [];
        foreach($rows as $row) {

            $row['registered'] = (bool)$row['registered'];
            $row['verified']   = (bool)$row['verified'];

            $row['avail'] = unserialize($row['avail']);
            $row['plans'] = unserialize($row['plans']);

            $regPersons[$row['regPersonId']] = new RegPerson($row);
            $regPersonIds[] = $row['regPersonId'];
        }
        if (count($regPersons) === 0) return $regPersons;

        // Use qb if ever need to repeat
        $sql = <<<EOT
SELECT
  id              AS regPersonRoleId,
  projectPersonId AS regPersonId,
  role            AS role,
  roleDate        AS roleDate,
  badge           AS badge,
  badgeDate       AS badgeDate,
  badgeUser       AS badgeUser,
  badgeExpires    AS badgeExpires,
  active          AS active,
  approved        AS approved,
  verified        AS verified,
  ready           AS ready,
  misc            AS misc,
  notes           AS notes
FROM     projectPersonRoles
WHERE    projectPersonId IN (?)
ORDER BY projectPersonId,role
EOT;

        // Join the roles, not doing any e3 checks here for now
        $rows = $this->regPersonConn->executeQuery($sql,[$regPersonIds],[Connection::PARAM_STR_ARRAY])->fetchAll();
        foreach($rows as $row) {
            $row['active']   = (bool)$row['active'];
            $row['approved'] = (bool)$row['approved'];
            $row['verified'] = (bool)$row['verified'];
            $row['ready']    = (bool)$row['ready'];

            $regPersonRole = new RegPersonRole($row);

            $regPersons[$row['regPersonId']]->addRole($regPersonRole);
        }
        return $regPersons;
    }
    public function findByProjectPerson(string $projectId, string $personId): ?RegPerson
    {
        $qb = $this->createRegPersonQueryBuilder();
        $qb->andWhere('projectKey = ?');
        $qb->andWhere('personKey  = ?');

        $rows = $this->regPersonConn->executeQuery($qb->getSQL(), [$projectId, $personId])->fetchAll();

        $regPersons = $this->processRegPersonRows($rows);

        // Better way to pop first element off the top?
        foreach($regPersons as $regPerson) {
            return $regPerson;
        }
        return null;
    }
    public function findByVarious(string $projectId, ?string $name = null, ?bool $registered = null, ?bool $verified = null) : RegPersons
    {
        $qb = $this->createRegPersonQueryBuilder();
        $qb ->andWhere('projectKey = ?');
        $params = [$projectId];
        if ($name) {
            $qb->andWhere('name LIKE ?');
            $params[] = '%'.$name.'%';
        }
        if ($registered !== null) {
            $qb->andWhere('registered = ?');
            $params[] = $registered;
        }
        if ($verified !== null) {
            $qb->andWhere('verified = ?');
            $params[] = $verified;
        }
        $qb->orderBy('name');

        $rows = $this->regPersonConn->executeQuery($qb->getSQL(),$params)->fetchAll();

        $regPersons = $this->processRegPersonRows($rows);

        return $regPersons;
    }
    public function findLatestProjectByPerson(string $personId) : ?string
    {
        $sql = 'SELECT projectKey,createdOn,updatedOn FROM projectPersons WHERE personKey = ? ORDER BY updatedOn DESC';

        $row = $this->regPersonConn->executeQuery($sql,[$personId])->fetch();

        return $row ? $row['projectKey'] : null;
    }
    public function findRegPersonPersons(int $regPersonId) : array
    {
        return [];
    }
    public function findRegPersonTeams(int $regPersonId) : array
    {
        return [];
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
        // TODO change back to user connection once implemented or just join
        $sql  = 'SELECT personKey AS personId, username FROM users WHERE personKey IN (?) ORDER BY name';
        $stmt = $this->regPersonConn->executeQuery($sql,[array_keys($persons)],[Connection::PARAM_STR_ARRAY]);
        $userChoices = [];
        while($row = $stmt->fetch()) {

            $person = $persons[$row['personId']];

            $userChoices[$row['username']] = $person['name'] . ' ' . $person['roles'];
        }
        return $userChoices;
    }
}