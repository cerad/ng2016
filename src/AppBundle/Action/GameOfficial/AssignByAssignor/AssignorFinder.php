<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignor;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use Cerad\Bundle\AysoBundle\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Project\User\ProjectUser;
use Doctrine\DBAL\Connection;

class AssignorFinder
{
    private $orgTransformer;
    private $regPersonConn;

    public function __construct(
        Connection $regPersonConn,
        RegionToSarTransformer $orgTransformer
    ) {
        $this->orgTransformer = $orgTransformer;
        $this->regPersonConn  = $regPersonConn;
    }
    public function findCrew(ProjectUser $user, GameOfficial $gameOfficial)
    {
        $personId  = $user->getPersonId();
        $projectId = $gameOfficial->projectId;
        $sql  = 'SELECT id,name FROM projectPersons WHERE projectKey = ? AND personKey = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$personId]);
        $row  = $stmt->fetch();
        if (!$row) {
            // Access violation
            return [];
        }
        $crew = [
            $projectId . ':' . $personId => $row['name'],
        ];

        return $crew;
    }
    public function findGameOfficialChoices(Game $game)
    {
        $sql = <<<EOD
SELECT 
    regPerson.personKey AS phyPersonId,
    regPerson.name      AS name,
    regPerson.orgKey    AS orgId,
    certReferee.badge   AS refereeBadge
    
FROM
  projectPersons AS regPerson
  
LEFT JOIN
  projectPersonRoles AS certReferee ON certReferee.projectPersonId = regPerson.id AND certReferee.role = 'CERT_REFEREE'
  
LEFT JOIN
  projectPersonRoles AS roleReferee ON roleReferee.projectPersonId = regPerson.id AND roleReferee.role = 'ROLE_REFEREE'

WHERE
  regPerson.projectKey = ? AND
  roleReferee.role = 'ROLE_REFEREE'
  
ORDER BY regPerson.name
EOD;
        $projectId = $game->projectId;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        $choices = [];
        while($row = $stmt->fetch()) {

            $regPersonId = $projectId . ':' . $row['phyPersonId'];

            $orgView = $this->orgTransformer->transform($row['orgId']);

            $desc = sprintf('%s -- %s -- %s',$row['name'],$row['refereeBadge'],$orgView);

            $choices[$regPersonId] = $desc;
        }
        return $choices;
    }
}