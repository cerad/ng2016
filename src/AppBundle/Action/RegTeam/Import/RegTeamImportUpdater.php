<?php
namespace AppBundle\Action\RegTeam\Import;

use AppBundle\Action\Game\RegTeam;
use Doctrine\DBAL\Connection;

class RegTeamImportUpdater
{
    private $regTeamConn;
    private $poolTeamConn;
    
    private $commit;

    /** @var  RegTeamImportResults */
    private $results;

    public function __construct(
        Connection $regTeamConn,
        Connection $poolTeamConn
    ) {
        $this->regTeamConn  = $regTeamConn;
        $this->poolTeamConn = $poolTeamConn;
    }
    /**
     * @param  array   $regTeams
     * @param  boolean $commit
     * @param  string  $fileName
     * @return RegTeamImportResults
     */
    public function updateRegTeams(array $regTeams, $commit, $fileName)
    {
        $this->commit  = $commit;
        $this->results = new RegTeamImportResults($regTeams,$commit,$fileName);

        foreach($regTeams as $regTeam) {
            $this->updateRegTeam($regTeam);
        }
        $this->results->calcCounts();
        
        return $this->results;
    }
    private function updateRegTeam($regTeam)
    {
        return;
        
        $poolTeamId  = $poolTeam['poolTeamId'];
        $poolTeamKey = $poolTeam['poolTeamKey'];

        // Delete Pool Team
        if (strpos($poolTeamKey,'DELETE ') === 0) {
            $this->deletePoolTeam($poolTeam);
            return;
        }
        // Verify have one
        $poolTeamRow = $this->findPoolTeam($poolTeamId);
        if (!$poolTeamRow) {
            // No create functionality for now
            return;
        }
        
        // Check for updates
        $updates = [];
        foreach(['poolView','poolSlotView','poolTypeView','poolTeamView','poolTeamSlotView'] as $key)
        if (strcmp($poolTeamRow[$key],$poolTeam[$key])) {
            $updates[$key] = $poolTeam[$key];
        }
        // Update if needed
        if (count($updates) < 1) {
            return;
        }
        $this->results->updatedPoolTeams[] = $poolTeam;
        if ($this->commit) {
            $this->conn->update('poolTeams', $updates, ['poolTeamId' => $poolTeamId]);
        }
    }
    private function deletePoolTeam($poolTeam)
    {
        $poolTeamId = $poolTeam['poolTeamId'];

        // See if it exists, multiple delete attempts are common
        $poolTeamRow = $this->findPoolTeam($poolTeamId);
        if (!$poolTeamRow) {
            return;
        }
        $this->results->deletedPoolTeams[] = $poolTeam;
        
        // Make sure not games are using the pool team
        $sql = <<<EOD
SELECT game.gameId, game.gameNumber, game.fieldName, game.start, gameTeam.poolTeamId
FROM games AS game
LEFT JOIN gameTeams AS gameTeam ON gameTeam.gameId = game.gameID
WHERE gameTeam.poolTeamId = ?
EOD;
        $stmt = $this->conn->executeQuery($sql,[$poolTeamId]);
        $gameRows = $stmt->fetchAll();
        if (count($gameRows)) {
            $this->results->existingGames = array_merge($this->results->existingGames,$gameRows);
            return;
        }
        
        if (!$this->commit) return;
        
        $this->conn->delete('poolTeams',['poolTeamId' => $poolTeamId]);
    }
    private function findPoolTeam($poolTeamId)
    {
        $sql = 'SELECT poolTeamId,poolTypeKey,poolView,poolSlotView,poolTypeView,poolTeamView,poolTeamSlotView FROM poolTeams WHERE poolTeamId = ?';
        $stmt = $this->conn->executeQuery($sql,[$poolTeamId]);
        return $stmt->fetch();
    }
}