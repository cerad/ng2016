<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignor;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\Project\User\ProjectUser;
use Doctrine\DBAL\Connection;

class AssignorFinder
{
    private $regPersonConn;

    public function __construct(Connection $regPersonConn)
    {
        $this->regPersonConn = $regPersonConn;
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
    regPerson.name      AS name
FROM
  projectPersons AS regPerson
WHERE
  regPerson.projectKey = ?
ORDER BY regPerson.name
EOD;
        $projectId = $game->projectId;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId]);
        $choices = [];
        while($row = $stmt->fetch()) {

            $regPersonId = $projectId . ':' . $row['phyPersonId'];

            $desc = $row['name'];

            $choices[$regPersonId] = $desc;
        }
        return $choices;
    }
}