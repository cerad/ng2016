<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

final class RegPersonFinder
{
    private $regPersonConn;

    public function __construct(RegPersonConnection $regPersonConn)
    {
        $this->regPersonConn = $regPersonConn;
    }
    public function findByProjectPerson(string $projectId, string $personId): ?RegPerson
    {
        $sql = <<<EOT
SELECT
  id         AS regPersonId,
  projectKey AS projectId,
  personKey  AS personId,
  orgKey     AS fedOrgId,
  fedKey     AS fedPersonId,
  regYear    AS regYear,
  registered AS registered,
  verified   AS verified,
  name       AS name,
  email      AS email,
  phone      AS phone,
  gender     AS gender,
  dob        AS dob,
  age        AS age,
  shirtSize  AS shirtSize,
  notes      AS notes,
  notesUser  AS notesUser,
  plans      AS plans,
  avail      AS avail,
  createdOn  AS createdOn,
  updatedOn  AS updatedOn,
  version    AS version
FROM  projectPersons
WHERE projectKey = ? AND personKey = ?
EOT;
        $row = $this->regPersonConn->executeQuery($sql, [$projectId, $personId])->fetch();

        if (!$row) {
            return null;
        }
        $row['registered'] = (bool)$row['registered'];
        $row['verified']   = (bool)$row['verified'];

        $row['avail'] = unserialize($row['avail']);
        $row['plans'] = unserialize($row['plans']);

        $regPerson = new RegPerson($row);

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
WHERE    projectPersonId = ?
ORDER BY role
EOT;

        $rows = $this->regPersonConn->executeQuery($sql,[$row['regPersonId']])->fetchAll();
        foreach($rows as $row) {
            $row['active']   = (bool)$row['active'];
            $row['approved'] = (bool)$row['approved'];
            $row['verified'] = (bool)$row['verified'];
            $row['ready']    = (bool)$row['ready'];

            $regPersonRole = new RegPersonRole($row);

            $regPerson->addRole($regPersonRole);
        }
        return $regPerson;
    }
    public function findLatestProjectByPerson(string $personId) : ?string
    {
        $sql = 'SELECT projectKey,createdOn,updatedOn FROM projectPersons WHERE personKey = ? ORDER BY updatedOn DESC';

        $row = $this->regPersonConn->executeQuery($sql,[$personId])->fetch();

        return $row ? $row['projectKey'] : null;
    }
}