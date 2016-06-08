<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\RegPerson\RegPersonFinder;
use Doctrine\DBAL\Connection;

class AssigneeFinder
{
    private $regPersonConn;
    private $regPersonFinder;
    
    public function __construct(
        Connection $regPersonConn,
        RegPersonFinder $regPersonFinder
    ) {
        $this->regPersonConn   = $regPersonConn;
        $this->regPersonFinder = $regPersonFinder;
    }
    public function findCrewChoices($regPersonId)
    {
        $crew = [];
        $regPersonPersons = $this->regPersonFinder->findRegPersonPersons($regPersonId);
        foreach($regPersonPersons as $regPersonPerson) {
            $crew[$regPersonPerson->memberId] = $regPersonPerson->memberName;
        }
        return $crew;
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