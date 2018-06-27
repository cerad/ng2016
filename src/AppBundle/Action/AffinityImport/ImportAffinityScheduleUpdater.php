<?php
namespace AppBundle\Action\Game\ImportAffinitySchedule;

use Doctrine\DBAL\Connection;

class ImportAffinityScheduleUpdater
{
    private $conn;
    private $commit;

    /** @var  ImportAffinityScheduleGameResults */
    private $gameResults;

    /** @var  ImportAffinityScheduleTeamResults */
    private $teamResults;

    /** @var  ImportAffinitySchedulePoolResults */
    private $poolResults;

    public function __construct(
        Connection $conn
    ) {
        $this->conn = $conn;
    }
    /**
     * @param  array   $games
     * @param  boolean $commit
     * @param  string  $fileName
     * @return ImportAffinityScheduleGameResults
     */
    public function updateGames(array $games, $commit, $fileName)
    {
        $this->commit  = $commit;
        $this->gameResults = new ImportAffinityScheduleGameResults($games,$commit,$fileName);

        foreach($games as $game) {
            $this->updateGame($game);
        }
        $this->gameResults->calcCounts();
        
        return $this->gameResults;
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
            $this->gameResults->invalidPoolTeamIds[] = $game['homePoolTeamId'];
        }
        if (!$awayPoolTeam) {
            $this->gameResults->invalidPoolTeamIds[] = $game['awayPoolTeamId'];
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
            $updates['finish'] = $game['finish'];
        }
        // Update if needed
        if (count($updates)) {
            $this->gameResults->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('games', $updates, ['gameId' => $gameId]);
            }
        }
        // Check for pool team changes, messy, refactor later
        $homeGameTeamRow = $this->findGameTeam($gameId,1);
        $awayGameTeamRow = $this->findGameTeam($gameId,2);
        if (strcmp($homeGameTeamRow['poolTeamId'],$homePoolTeamId)) {
            $this->gameResults->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('gameTeams',
                    ['poolTeamId' => $homePoolTeamId],
                    ['gameTeamId' => $homeGameTeamRow['gameTeamId']]);
            }
        }
        if (strcmp($awayGameTeamRow['poolTeamId'],$awayPoolTeamId)) {
            $this->gameResults->addUpdatedGame($game);
            if ($this->commit) {
                $this->conn->update('gameTeams',
                    ['poolTeamId' => $awayPoolTeamId],
                    ['gameTeamId' => $awayGameTeamRow['gameTeamId']]);
            }
        }
    }
    private function createGame($game,$homePoolTeam,$awayPoolTeam)
    {
        $this->gameResults->createdGames[] = $game;

        if (!$this->commit) return;

        $gameId     = $game['gameId'];
        $projectId  = $game['projectId'];
        $gameNumber = $game['gameNumber'];

        $gameRow = [
            'gameId'     => $gameId,
            'projectId'  => $projectId,
            'gameNumber' => $gameNumber,
            'fieldName'  => $game['fieldName'],
            'venueName'  => $game['venuName'],
            'start'      => $game['start'],
            'finish'     => $game['finish'],
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

        $this->gameResults->deletedGames[] = $game;

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
            'U10'   => 40 +  5,
            'U11'   => 50 +  5,
            'U12'   => 50 +  5,
            'U13'   => 50 + 10,
            'U14'   => 50 + 10,
            'U16'   => 60 + 10,
            'U19'   => 60 + 10,
            'Adult' => 50 + 10,
        ];
        $finishDateTime = new \DateTime($start);

        $age = $poolTeam['age'];

        $interval = sprintf('PT%dM',$lengths[$age]);

        $finishDateTime->add(new \DateInterval($interval));

        return $finishDateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param  array   $teams
     * @param  boolean $commit
     * @param  string  $fileName
     * @return ImportAffinityScheduleTeamResults
     */
    public function updateRegTeams(array $teams, $commit, $fileName)
    {
        $this->commit  = $commit;
        $this->gameResults = new ImportAffinityScheduleTeamResults($teams,$commit,$fileName);

        foreach($teams as $team) {
            $this->updateRegTeam($team);
        }
        $this->gameResults->calcCounts();

        return $this->teamResults;
    }

    protected function updateRegTeam($team)
    {
        // TODO: code updateRegTeam
        return;
    }

    /**
     * @param  array   $pools
     * @param  boolean $commit
     * @param  string  $fileName
     * @return ImportAffinitySchedulePoolResults
     */
    public function updatePoolTeams(array $pools, $commit, $fileName)
    {
        $this->commit  = $commit;
        $this->gameResults = new ImportAffinityScheduleGameResults($pools,$commit,$fileName);

        foreach($pools as $pool) {
            $this->updatePoolTeam($pool);
        }
        $this->gameResults->calcCounts();


        return $this->poolResults;
    }

    protected function updatePoolTeam($pool)
    {
        // TODO: code updatePoolTeam

        return;
    }
    
}