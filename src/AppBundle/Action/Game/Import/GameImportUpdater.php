<?php
namespace AppBundle\Action\Game\Import;

use Doctrine\DBAL\Connection;

class GameImportUpdater
{
    private $conn;
    private $commit;

    /** @var  GameImportResults */
    private $results;

    public function __construct(
        Connection $conn
    ) {
        $this->conn = $conn;
    }
    /**
     * @param  array   $games
     * @param  boolean $update
     * @return GameImportResults
     */
    public function updateGames(array $games, $commit)
    {
        $this->commit  = $commit;
        $this->results = new GameImportResults();
        $this->results->totalCount = count($games);

        foreach($games as $game) {
            $this->updateGame($game);
        }
        return $this->results;
    }
    private function updateGame($game)
    {
        $gameId = $game['gameId'];
        $gameNumber = $game['gameNumber'];

        // Delete Game
        if ($gameNumber < 0) {
            $this->removeGame($gameId);
            return;
        }
        // Create Game
        $gameRow = $this->findGame($gameId);
        if (!$gameRow) {
            $this->createGame($game);
            return;
        }
        // Update game
    }
    private function createGame($game)
    {
        $this->results->createdCount++;

        if (!$this->commit) return;

    }
    private function removeGame($gameId)
    {
        // For stats see if it exists, multiple delete attempts are common
        if (!$this->findGame($gameId)) return;

        $this->results->deletedCount++;

        if (!$this->commit) return;

        // TODO Add foriegn key references and cascade delete
        $id = ['gameId' => $gameId];
        $this->conn->delete('gameTeams',    $id);
        $this->conn->delete('gameOfficials',$id);
        $this->conn->delete('games',        $id);
    }
    private function findGame($gameId)
    {
        $sql = 'SELECT gameId,fieldName,start FROM games WHERE gameId = ?';
        $stmt = $this->conn->executeQuery($sql,[$gameId]);
        return $stmt->fetch();
    }
}