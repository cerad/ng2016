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
     * @param  boolean $commit
     * @param  string  $fileName
     * @return GameImportResults
     */
    public function updateGames(array $games, $commit, $fileName)
    {
        $this->commit  = $commit;
        $this->results = new GameImportResults($games,$commit,$fileName);

        foreach($games as $game) {
            $this->updateGame($game);
        }
        $this->results->calcCounts();
        
        return $this->results;
    }
    private function updateGame($game)
    {
        $gameId = $game['gameId'];
        
        $gameNumber = $game['gameNumber'];

        // Delete Game
        if ($gameNumber < 0) {
            $this->removeGame($game);
            return;
        }
        // Must have valid pool teams
        $homePoolTeamId = $game['homePoolTeamId'];
        $awayPoolTeamId = $game['awayPoolTeamId'];
        $homePoolTeam = $this->findPoolTeam($homePoolTeamId);
        $awayPoolTeam = $this->findPoolTeam($awayPoolTeamId);

        if (!$homePoolTeam) {
            $this->results->invalidPoolTeamIds[] = $game['homePoolTeamId'];
        }
        if (!$awayPoolTeam) {
            $this->results->invalidPoolTeamIds[] = $game['awayPoolTeamId'];
        }
        if (!$homePoolTeam || !$awayPoolTeam) {
            return;
        }
        // Create Game
        $gameRow = $this->findGame($gameId);
        if (!$gameRow) {
            $this->createGame($game,$homePoolTeam,$awayPoolTeam);
            return;
        }
        // Update game
        $updates = [];

        // Field name
        if (strcmp($gameRow['fieldName'],$game['fieldName'])) {
            $updates['fieldName'] = $game['fieldName'];
        }
        // Start time
        if (strcmp($gameRow['start'],$game['start'])) {

            $updates['start']  = $game['start'];
            $updates['finish'] = $this->calcFinish($game['start'],$homePoolTeam);
        }
        // Update if needed
        if (count($updates)) {
            $this->results->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('games', $updates, ['gameId' => $gameId]);
            }
        }
        // Check for pool team changes, messy, refactor later
        $homeGameTeamRow = $this->findGameTeam($gameId,1);
        $awayGameTeamRow = $this->findGameTeam($gameId,2);
        if (strcmp($homeGameTeamRow['poolTeamId'],$homePoolTeamId)) {
            $this->results->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('gameTeams',
                    ['poolTeamId' => $homePoolTeamId],
                    ['gameTeamId' => $homeGameTeamRow['gameTeamId']]);
            }
        }
        if (strcmp($awayGameTeamRow['poolTeamId'],$awayPoolTeamId)) {
            $this->results->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('gameTeams',
                    ['poolTeamId' => $awayPoolTeamId],
                    ['gameTeamId' => $awayGameTeamRow['gameTeamId']]);
            }
        }
    }
    private function createGame($game,$homePoolTeam,$awayPoolTeam)
    {
        $this->results->createdGames[] = $game;

        if (!$this->commit) return;

    }
    private function removeGame($game)
    {
        $gameId = $game['gameId'];
        
        // For stats see if it exists, multiple delete attempts are common
        if (!$this->findGame($gameId)) return;

        $this->results->deletedGames[] = $game;

        if (!$this->commit) return;

        // TODO Add foreign key references and cascade delete
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
    private function findGameTeam($gameId,$slot)
    {
        $sql = 'SELECT gameTeamId,poolTeamId FROM gameTeams WHERE gameId = ? AND slot = ?';
        $stmt = $this->conn->executeQuery($sql,[$gameId,$slot]);
        return $stmt->fetch();
    }
    private function findPoolTeam($poolTeamId)
    {
        $sql = 'SELECT poolTeamId,age FROM poolTeams WHERE poolTeamId = ?';
        $stmt = $this->conn->executeQuery($sql,[$poolTeamId]);
        return $stmt->fetch();
    }
    private function calcFinish($start,$poolTeam)
    {
        $lengths = [
            'U10' => 40 +  5,
            'U12' => 50 +  5,
            'U14' => 50 + 10,
            'U16' => 60 + 10,
            'U19' => 60 + 10,
        ];
        $finishDateTime = new \DateTime($start);

        $age = $poolTeam['age'];

        $interval = sprintf('PT%dM',$lengths[$age]);

        $finishDateTime->add(new \DateInterval($interval));

        return $finishDateTime->format('Y-m-d H:i:s');
    }
}