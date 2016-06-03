<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\Project\User\ProjectUser;
use Doctrine\DBAL\Connection;

class AssigneeFinder
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
}