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
            //$this->gameConn->update('gameOfficials',['assignState' => $gameOfficial->assignState],$id);
        }
        //dump($gameOfficial);
        //dump($gameOfficialOriginal);
        // The person
        if ($gameOfficial->regPersonId === $gameOfficialOriginal->regPersonId) {
            //return; // Something is not clearing right, state is being updated to Open
        }
        $gameOfficialUpdateInfo = [
            'phyPersonId'   => null,
            'regPersonId'   => null,
            'regPersonName' => null,
            'assignState'   => $gameOfficial->assignState,
        ];
        $regPersonId = $gameOfficial->regPersonId;
        if ($regPersonId && $gameOfficial->assignState !== 'Open') {
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
                'assignState'   => $gameOfficial->assignState,
            ];
        }
        //dump($gameOfficialUpdateInfo);
        $this->gameConn->update('gameOfficials',$gameOfficialUpdateInfo,$id);
    }
}
