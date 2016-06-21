<?php
namespace AppBundle\Action\GameOfficial;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use Doctrine\DBAL\Connection;

class GameOfficialDetailsFinder
{
    private $orgFinder;
    
    private $gameConn;
    private $regPersonConn;

    public function __construct(
        Connection $gameConn,
        Connection $regPersonConn,
        RegionToSarTransformer $orgFinder
    ) {
        $this->gameConn      = $gameConn;
        $this->regPersonConn = $regPersonConn;
        
        $this->orgFinder = $orgFinder;
    }
    public function findGameOfficialDetails($regPersonId)
    {
        if (!$regPersonId) { // auto in integer still
            return null;
        }
        if (count(explode(':',$regPersonId)) < 2) {
            return null;
        };
        list($projectId,$phyPersonId) = explode(':',$regPersonId);

        $sql = <<<EOD
SELECT 
  regPerson.id AS regPersonId,
  
  projectKey AS projectId,
  personKey  AS personId,
  orgKey     AS orgId,
  fedKey     AS fedId,
  regYear    AS regYear,
  name       AS name,
  email      AS email,
  phone      AS phone,
  shirtSize  AS shirtSize,
  badge      AS refereeBadge,
  age        AS age
FROM projectPersons as regPerson
LEFT JOIN 
  projectPersonRoles AS regPersonRole 
  ON  regPersonRole.projectPersonId = regPerson.id 
  AND regPersonRole.role = 'CERT_REFEREE'
WHERE
  regPerson.projectKey = ? AND regPerson.personKey = ?
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null; // Exception
        }
        // Really need a sars view property
        $row['orgView'] = $this->orgFinder->transform($row['orgId']);

        return $row;
    }
}
