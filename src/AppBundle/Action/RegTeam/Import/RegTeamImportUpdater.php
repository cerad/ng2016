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
        $regTeamId  = $regTeam['regTeamId'];
        $regTeamKey = $regTeam['regTeamKey'];

        // Delete Reg Team
        if (strpos($regTeamKey,'DELETE ') === 0) {
            $this->deleteRegTeam($regTeam);
            return;
        }
        // Verify have one
        $regTeamRow = $this->findRegTeam($regTeamId);
        if (!$regTeamRow) {
            // No create functionality for now
            return;
        }
        // Check for updates
        $updates = [];
        foreach(['teamName','orgId','orgView'] as $key)
        if (strcmp($regTeamRow[$key],$regTeam[$key])) {
            $updates[$key] = $regTeam[$key];
        }
        // Update if needed
        if (count($updates)) {
            $this->results->updatedRegTeams[] = $regTeam;
            if ($this->commit) {
                $this->regTeamConn->update('regTeams', $updates, ['regTeamId' => $regTeamId]);
            }
        }
        // Assign to pool teams TODO Check soccerfest points
        foreach($regTeam['poolTeamKeys'] as $poolTeamKey) {
            $this->updatePoolTeam($regTeam,$poolTeamKey);
        }
    }
    private function updatePoolTeam($regTeam,$poolTeamKey)
    {
        if (!$poolTeamKey) {
            return;
        }
        if ($poolTeamKey[0] === '~') {
            $regTeam['regTeamId']     = null;
            $regTeam['regTeamName']   = null;
            $regTeam['regTeamPoints'] = null;
            $poolTeamKey = substr($poolTeamKey,1);;
        }
        $sql  = 'SELECT poolTypeKey,regTeamId,regTeamName,regTeamPoints FROM poolTeams WHERE projectId = ? AND poolTeamKey = ?';
        $stmt = $this->poolTeamConn->executeQuery($sql,[$regTeam['projectId'],$poolTeamKey]);
        $row = $stmt->fetch();
        if (!$row) {
            return; // Really should not happen
        }
        $updates = [];
        if (strcmp($regTeam['regTeamId'],$row['regTeamId'])) {
            $updates['regTeamId'] = $regTeam['regTeamId'];
        }
        if (strcmp($regTeam['teamName'],$row['regTeamName'])) {
            $updates['regTeamName'] = $regTeam['teamName'];
        }
        if ($row['poolTypeKey'] === 'PP') {
            $points = strlen($row['regTeamPoints']) ? (integer)$row['regTeamPoints'] : null;
            if ($regTeam['points'] !== $points) {
                $updates['regTeamPoints'] = $regTeam['points'];
            }
        }
        if (count($updates) < 1) {
            return;
        }
        $this->results->updatedPoolTeams[] = $row;
        if (!$this->commit) {
            return;
        }
        dump($updates);
        $this->poolTeamConn->update('poolTeams',$updates,
            ['projectId' => $regTeam['projectId'], 'poolTeamKey' => $poolTeamKey]);
    }
    private function deleteRegTeam($regTeam)
    {
        $regTeamId = $regTeam['regTeamId'];

        // See if it exists, multiple delete attempts are common
        $regTeamRow = $this->findRegTeam($regTeamId);
        if (!$regTeamRow) {
            return;
        }
        $this->results->deletedRegTeams[] = $regTeam;

        if (!$this->commit) return;

        // Reset any pool teams links here
        $sql = <<<EOD
SELECT poolTeamId
FROM   poolTeams AS poolTeam
WHERE  poolTeam.regTeamId = ?
EOD;
        $stmt = $this->poolTeamConn->executeQuery($sql,[$regTeamId]);
        while($row = $stmt->fetch()) {
            $this->poolTeamConn->update('poolTeams',['regTeamId' => null],['poolTeamId' => $row['poolTeamId']]);
        }
        $this->regTeamConn->delete('regTeams',['regTeamId' => $regTeamId]);
    }
    private function findRegTeam($regTeamId)
    {
        $sql = 'SELECT regTeamId,teamName,orgId,orgView FROM regTeams WHERE regTeamId = ?';
        $stmt = $this->regTeamConn->executeQuery($sql,[$regTeamId]);
        return $stmt->fetch();
    }
}