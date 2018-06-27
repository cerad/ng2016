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

        $gameId     = $game['gameId'];
        $projectId  = $game['projectId'];
        $gameNumber = $game['gameNumber'];

        $gameRow = [
            'gameId'     => $gameId,
            'projectId'  => $projectId,
            'gameNumber' => $gameNumber,
            'fieldName'  => $game['fieldName'],
            'venueName'  => 'LNSC',
            'start'      => $game['start'],
            'finish'     => $this->calcFinish($game['start'],$homePoolTeam),
        ];
        $this->conn->insert('games',$gameRow);

        // Home Team
        $slot = 1;
        $gameTeam = [
            'projectId'  => $projectId,
            'gameId'     => $gameId,
            'gameNumber' => $gameNumber,
            'poolTeamId' => $homePoolTeam['poolTeamId'],
            'slot'       => $slot,
            'gameTeamId' => $gameId . ':' . $slot,
        ];
        $this->conn->insert('gameTeams',$gameTeam);

        // Away Team
        $slot = 2;
        $gameTeam = array_replace($gameTeam,[
            'poolTeamId' => $awayPoolTeam['poolTeamId'],
            'slot'       => $slot,
            'gameTeamId' => $gameId . ':' . $slot,
        ]);
        $this->conn->insert('gameTeams',$gameTeam);

        // Officials
        $isMedalRound = in_array($homePoolTeam['poolTypeKey'],['QF','SF','TF']);
        $gameOfficial = [
            'projectId'   => $projectId,
            'gameId'      => $gameId,
            'gameNumber'  => $gameNumber,
            'assignRole'  => $isMedalRound ? 'ROLE_ASSIGNOR' : 'ROLE_REFEREE',
            'assignState' => 'Open',
        ];
        foreach([1,2,3] as $slot) {
            $gameOfficial['gameOfficialId'] = $gameId . ':' . $slot;
            $gameOfficial['slot'] = $slot;
            $this->conn->insert('gameOfficials',$gameOfficial);
        }
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
        $sql = 'SELECT poolTeamId,poolTypeKey,age FROM poolTeams WHERE poolTeamId = ?';
        $stmt = $this->conn->executeQuery($sql,[$poolTeamId]);
        return $stmt->fetch();
    }
    private function calcFinish($start,$poolTeam)
    {
        $lengths = [
            'VIP' => 20 + 5,
            '10U' => 40 + 5,
            '11U' => 50 + 5,
            '12U' => 50 + 5,
            '13U' => 50 + 10,
            '14U' => 50 + 10,
            '16U' => 60 + 10,
            '19U' => 60 + 10,
            '2008' => 40 + 5,
            '2007' => 40 + 5,
            '2006' => 50 + 5,
            '2005' => 50 + 5,
            '2004' => 50 + 5,
            '2003' => 60 + 5,
            '2002' => 60 + 5,
        ];
        $finishDateTime = new \DateTime($start);

        $age = $poolTeam['age'];

        $interval = sprintf('PT%dM',$lengths[$age]);

        $finishDateTime->add(new \DateInterval($interval));

        return $finishDateTime->format('Y-m-d H:i:s');
    }
}