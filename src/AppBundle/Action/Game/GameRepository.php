<?php
namespace AppBundle\Action\Game;

use Doctrine\DBAL\Connection;

class GameRepository
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param  GameId $gameId
     * @return Game|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function find(GameId $gameId)
    {
        // Load the game row
        $stmt = $this->conn->executeQuery('SELECT * FROM projectGames WHERE id = ?',[$gameId]);
        $gameRow  = $stmt->fetch();
        if (!$gameRow) {
            return null;
        }
        $gameArray = $gameRow;
        $gameArray['gameNumber'] = (integer)$gameRow['gameNumber'];
        $gameArray['teams'] = [];

        // Done
        return Game::fromArray($gameArray);
    }
    /** ==========================================================
     * @param  Game $game
     * @return Game
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save(Game $game)
    {
        $gameArray = $game->toArray();
        
        // Stash the teams
        //$gameTeamsArray = $gameArray['teams'];
        unset($gameArray['teams']);
        
        // Pull the id
        $gameId = $gameArray['id'];
        
        // Does it exist (update/create)
        $stmt = $this->conn->executeQuery('SELECT id FROM projectGames WHERE id = ?',[$gameId]);
        if ($stmt->fetch()) {
            unset($gameArray['id']);
            $this->conn->update('projectGames',$gameArray,[$gameId]);
            $gameArray['id'] = $gameId;
        }
        else {
            $this->conn->insert('projectGames',$gameArray);
        }
        
        // Done
        return Game::fromArray($gameArray);
    }
}
