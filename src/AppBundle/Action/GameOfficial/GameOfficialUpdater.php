<?php
namespace AppBundle\Action\GameOfficial;

use AppBundle\Action\Game\GameOfficial;

use Doctrine\DBAL\Connection;

class GameOfficialUpdater
{
    private $gameConn;
    private $regPersonConn;

    public function __construct(
        Connection $gameConn,
        Connection $regPersonConn
    ) {
        $this->gameConn      = $gameConn;
        $this->regPersonConn = $regPersonConn;
    }
    public function updateGameOfficial(GameOfficial $gameOfficial, GameOfficial $gameOfficialOriginal)
    {
        $id = ['gameOfficialId' => $gameOfficial->gameOfficialId];

        // The state
        if ($gameOfficial->assignState !== $gameOfficialOriginal->assignState) {
            $this->gameConn->update('gameOfficials',['assignState' => $gameOfficial->assignState],$id);
        }
        // The person
        if ($gameOfficial->regPersonId === $gameOfficialOriginal->regPersonId) {
            return;
        }
        $gameOfficialUpdateInfo = [
            'phyPersonId'   => null,
            'regPersonId'   => null,
            'regPersonName' => null,
        ];
        $regPersonId = $gameOfficial->regPersonId;
        if ($regPersonId) {
            list($projectId,$phyPersonId) = explode(':',$regPersonId);
            $sql  = 'SELECT name FROM projectPersons WHERE projectKey = ? AND personKey = ?';
            $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
            $row  = $stmt->fetch();
            if (!$row) {
                // Access violation
                return;
            }
            $gameOfficialUpdateInfo = [
                'phyPersonId'   => $phyPersonId,
                'regPersonId'   => $regPersonId,
                'regPersonName' => $row['name'],
            ];
        }
        $this->gameConn->update('gameOfficials',$gameOfficialUpdateInfo,$id);
    }
}
