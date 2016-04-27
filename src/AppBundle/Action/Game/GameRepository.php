<?php
namespace AppBundle\Action\Game;

use Doctrine\DBAL\Connection;

class GameRepository
{
    /** @var  Connection */
    private $conn;

    /** @var PoolTeamRepository  */
    private $poolTeamRepository;

    public function __construct(
        Connection $conn,
        PoolTeamRepository $poolTeamRepository = null
    )
    {
        $this->conn = $conn;
        $this->poolTeamRepository = $poolTeamRepository;
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

        // Load the teams
        $stmt = $this->conn->executeQuery('SELECT * FROM projectGameTeams WHERE gameId = ?',[$gameId]);
        $poolTeamIds = [];
        while($gameTeamRow = $stmt->fetch()) {
            $gameTeamRow['poolTeam'] = null;
            $gameArray['teams'][$gameTeamRow['slot']] = $gameTeamRow;
            if ($gameTeamRow['poolTeamId']) {
                $poolTeamIds[$gameTeamRow['poolTeamId']] = $gameTeamRow;
            }
        }
        // Load the pool teams, very screwy look indeed
        if (count($poolTeamIds)) {
            $poolTeams = $this->poolTeamRepository->findBy(['ids' => array_keys($poolTeamIds)]);
            foreach($poolTeams as $poolTeam) {
                $poolTeamId  = $poolTeam->id->id;
                $gameTeamRow = $poolTeamIds[$poolTeamId];
                $gameTeamRow['poolTeam'] = $poolTeam->toArray();
                $gameArray['teams'][$gameTeamRow['slot']] = $gameTeamRow;
            }
        }
        //var_dump($gameArray);
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
        $gameTeamsArray = $gameArray['teams'];
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
        $gameArray['teams'] = [];
        foreach($gameTeamsArray as $slot => $gameTeamArray)
        {
            // TODO Maybe verify team is connected to correct game?
            $gameTeamArray['gameId'] = $gameId;

            $this->saveGameTeamArray($gameTeamArray);

            $gameArray['teams'][$slot] = $gameTeamArray;
        }

        // Done
        return Game::fromArray($gameArray);
    }
    private function saveGameTeamArray(array $gameTeamArray)
    {
        $gameTeamId = $gameTeamArray['id'];
        
        // Reduce pool team to id
        $poolTeamArray = $gameTeamArray['poolTeam'];
        unset($gameTeamArray['poolTeam']);
        $gameTeamArray['poolTeamId'] = isset($poolTeamArray['id']) ? $poolTeamArray['id'] : null;
        
        // Does it exist (update/create)
        $stmt = $this->conn->executeQuery('SELECT id FROM projectGameTeams WHERE id = ?',[$gameTeamId]);
        if ($stmt->fetch()) {
            unset($gameTeamArray['id']);
            $this->conn->update('projectGameTeams',$gameTeamArray,[$gameTeamId]);
            $gameTeamArray['id'] = $gameTeamId;
        }
        else {
            $this->conn->insert('projectGameTeams',$gameTeamArray);
        }

        // var_dump($gameTeamArray); die();
    }
}
