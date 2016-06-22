<?php
namespace AppBundle\Action\Game;

use Doctrine\DBAL\Connection;

class GameUpdater
{
    private $gameConn;

    public function __construct(
        Connection $gameConn
    ) {
        $this->gameConn = $gameConn;
    }
    public function deleteGame($projectId,$gameNumber)
    {
        $id = ['projectId' => $projectId, 'gameNumber' => $gameNumber];
        $this->gameConn->delete('gameTeams',    $id);
        $this->gameConn->delete('gameOfficials',$id);
        $this->gameConn->delete('games',        $id);
    }

    /* ==========================================
     * This will currently fail hard if the new number already exists
     * 
     */
    public function changeGameNumber($projectId,$gameNumberOld,$gameNumberNew)
    {
        //$gameIdOld = $projectId . ':' . $gameNumberOld;
        $gameIdNew = $projectId . ':' . $gameNumberNew;
        $idOld = [
            'projectId'  => $projectId,
            'gameNumber' => $gameNumberOld,
        ];
        // Start with teams
        $sql  = 'SELECT slot FROM gameTeams WHERE projectId = ? AND gameNumber = ? ORDER BY slot';
        $stmt = $this->gameConn->executeQuery($sql,array_values($idOld));
        while($row = $stmt->fetch()) {
            $slot = $row['slot'];
            $gameTeamId = $gameIdNew . ':' . $slot;
            $updates = [
                'gameTeamId' => $gameTeamId,
                'gameId'     => $gameIdNew,
                'gameNumber' => $gameNumberNew,
            ];
            $id = array_merge($idOld,['slot' => $slot]);
            //VarDumper::dump($updates);
            $this->gameConn->update('gameTeams',$updates,$id);
        }
        // Then officials
        $sql  = 'SELECT slot FROM gameOfficials WHERE projectId = ? AND gameNumber = ? ORDER BY slot';
        $stmt = $this->gameConn->executeQuery($sql,array_values($idOld));
        while($row = $stmt->fetch()) {
            $slot = $row['slot'];
            $gameTeamId = $gameIdNew . ':' . $slot;
            $updates = [
                'gameOfficialId' => $gameTeamId,
                'gameId'         => $gameIdNew,
                'gameNumber'     => $gameNumberNew,
            ];
            $id = array_merge($idOld,['slot' => $slot]);
            $this->gameConn->update('gameOfficials',$updates,$id);
        }
        // And the game
        $updates = [
            'gameId'     => $gameIdNew,
            'gameNumber' => $gameNumberNew,
        ];
        $this->gameConn->update('games',$updates,$idOld);
    }
}
