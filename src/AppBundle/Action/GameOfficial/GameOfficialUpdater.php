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
        if ($gameOfficial->regPersonId) {
            $sql  = 'SELECT personKey, name FROM projectPersons WHERE id = ?';
            $stmt = $this->regPersonConn->executeQuery($sql,[$gameOfficial->regPersonId]);
            $row  = $stmt->fetch();
            if (!$row) {
                // Access violation
                return;
            }
            $gameOfficialUpdateInfo = [
                'phyPersonId'   => $row['personKey'],
                'regPersonId'   => $gameOfficial->regPersonId,
                'regPersonName' => $row['name'],
            ];
        }
        $this->gameConn->update('gameOfficials',$gameOfficialUpdateInfo,$id);
    }
}
