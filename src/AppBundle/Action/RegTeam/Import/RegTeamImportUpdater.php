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
        $this->regTeamConn = $regTeamConn;
        $this->poolTeamConn = $poolTeamConn;
    }

    /**
     * @param  array $regTeams
     * @param  boolean $commit
     * @param  string $fileName
     * @return RegTeamImportResults
     */
    public function updateRegTeams(array $regTeams, $commit, $fileName)
    {
        $this->commit = $commit;
        $this->results = new RegTeamImportResults($regTeams, $commit, $fileName);

        foreach ($regTeams as $regTeam) {
            $this->updateRegTeam($regTeam);
        }
        $this->results->calcCounts();

        return $this->results;
    }

    private function updateRegTeam($regTeam)
    {
        $regTeamId = $regTeam['regTeamId'];

        // Delete Reg Team
        if ($regTeam['regTeamDelete']) {
            $this->deleteRegTeam($regTeam);

            return;
        }
        // Verify have one
        $regTeamRow = $this->findRegTeam($regTeamId);
        if (!$regTeamRow) {
            // Create functionality
            $this->createRegTeam($regTeam);

            return;
        }
        // Check for updates
        $updates = [];
        foreach (['regTeamName' => 'teamName', 'orgId' => 'orgId', 'orgView' => 'orgView'] as $key => $colName) {
            if (strcmp($regTeamRow[$key], $regTeam[$key])) {
                $updates[$colName] = $regTeam[$key];
            }
        }
        // Update if needed
        if (count($updates)) {
            $this->results->updatedRegTeams[] = $regTeam;
            if ($this->commit) {
                $this->regTeamConn->update('regTeams', $updates, ['regTeamId' => $regTeamId]);
            }
        }
        // Assign to pool teams TODO Check soccerfest points
        foreach ($regTeam['poolTeamKeys'] as $poolTeamKey) {
            $this->updatePoolTeam($regTeam, $poolTeamKey);
        }
    }

    private function updatePoolTeam($regTeam, $poolTeamKey)
    {
        if (!$poolTeamKey) {
            return;
        }
        if ($poolTeamKey[0] === '~') {
            $regTeam['regTeamId'] = null;
            $regTeam['regTeamName'] = null;
            $regTeam['regTeamPoints'] = null;
            $poolTeamKey = substr($poolTeamKey, 1);;
        }
        $sql = 'SELECT poolTypeKey,regTeamId,regTeamName,regTeamPoints FROM poolTeams WHERE projectId = ? AND poolTeamKey = ?';
        $stmt = $this->poolTeamConn->executeQuery($sql, [$regTeam['projectId'], $poolTeamKey]);
        $row = $stmt->fetch();
        if (!$row) {
            return; // Really should not happen
        }
        $updates = [];
        if (strcmp($regTeam['regTeamId'], $row['regTeamId'])) {
            $updates['regTeamId'] = $regTeam['regTeamId'];
        }
        if (strcmp($regTeam['regTeamName'], $row['regTeamName'])) {
            $updates['regTeamName'] = $regTeam['regTeamName'];
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
        $this->poolTeamConn->update(
            'poolTeams',
            $updates,
            ['projectId' => $regTeam['projectId'], 'poolTeamKey' => $poolTeamKey]
        );
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

        if (!$this->commit) {
            return;
        }

        // Reset any pool teams links here
        $sql = <<<EOD
SELECT poolTeamId
FROM   poolTeams AS poolTeam
WHERE  poolTeam.regTeamId = ?
EOD;
        $stmt = $this->poolTeamConn->executeQuery($sql, [$regTeamId]);
        while ($row = $stmt->fetch()) {
            $this->poolTeamConn->update(
                'poolTeams',
                ['regTeamId' => null, 'regTeamName' => null, 'regTeamPoints' => null],
                ['poolTeamId' => $row['poolTeamId']]
            );
        }
        $this->regTeamConn->delete('regTeams', ['regTeamId' => $regTeamId]);
    }

    private function findRegTeam($regTeamId)
    {
        $sql = 'SELECT regTeamId,teamName AS regTeamName,orgId,orgView FROM regTeams WHERE regTeamId = ?';
        $stmt = $this->regTeamConn->executeQuery($sql, [$regTeamId]);

        return $stmt->fetch();
    }

    private function findPoolTeam($poolTeamId)
    {
        $sql = 'SELECT * FROM poolTeams WHERE poolTeamId = ?';
        $stmt = $this->poolTeamConn->executeQuery($sql, [$poolTeamId]);

        return $stmt->fetch();
    }

    private function createRegTeam($regTeam)
    {
        $this->results->createdRegTeams[] = $regTeam;

        if (!$this->commit) {
            $this->results->updatedPoolTeams[] = $regTeam;
            return;
        }

        $projectId = $regTeam['projectId'];

        $regTeamKeys = [
            'gender',
            'age',
            'program',
            'teamNumber',
        ];
        $regTeamValues = explode(' ', $regTeam['regTeamKey']);
        if (count($regTeamValues) == count($regTeamKeys)) {
            $team = array_combine($regTeamKeys, $regTeamValues);
            $regTeamKey = sprintf('%s%s%s%02d', $team['gender'], $team['age'], $team['program'], $team['teamNumber']);
            $regTeamId = $projectId.':'.$regTeamKey;
        } else {
            return;
        }

        // Verify doesn't exist
        $regTeamRow = $this->findRegTeam($regTeamId);
        if ($regTeamRow) {
            return;
        }

        $teamNumber = $team['teamNumber'];
        $regTeamName = $regTeam['regTeamName'];
        $orgId = $regTeam['orgId'];
        $orgView = $regTeam['orgView'];
        $program = $team['program'];
        $age = $team['age'];
        $gender = $team['gender'];
        $division = $gender.$age;

        $regTeamRow = [
            'regTeamId' => $regTeamId,
            'projectId' => $projectId,
            'teamKey' => $regTeamKey,
            'teamNumber' => $teamNumber,
            'teamName' => $regTeamName,
            'teamPoints' => 0,
            'orgId' => $orgId,
            'orgView' => $orgView,
            'program' => $program,
            'gender' => $gender,
            'age' => $age,
            'division' => $division,
        ];
        $this->regTeamConn->insert('regTeams', $regTeamRow);

        $poolTeamKeys = [
            'gender',
            'age',
            'program',
            'poolTypeKey',
            'poolSlotView',
            'poolTeamSlot',
        ];
        $poolTeamValues = explode(' ', $regTeam['poolTeamKeys'][0]);
        if (count($poolTeamKeys) == count($poolTeamValues)) {
            $team = array_combine($poolTeamKeys, $poolTeamValues);
            $poolKey = sprintf(
                '%s%s%s%s%s',
                $team['gender'],
                $team['age'],
                $team['program'],
                $team['poolTypeKey'],
                $team['poolSlotView']
            );
            $poolTypeKey = $team['poolTypeKey'];
            $poolTeamKey = $poolKey.$team['poolTeamSlot'];
            $poolTeamId = $projectId.':'.$poolTeamKey;
            $poolView = sprintf(
                '%s%s %s %s %s',
                $team['gender'],
                $team['age'],
                $team['program'],
                $team['poolTypeKey'],
                $team['poolSlotView']
            );
            $poolTeamSlotView = sprintf('%s%s', $team['poolSlotView'], $team['poolTeamSlot']);
            $poolTeamView = sprintf(
                '%s%s %s %s',
                $team['gender'],
                $team['age'],
                $team['program'],
                $poolTeamSlotView
            );
        } else {
            return;
        }

        // Verify doesn't exist
        $poolTeamRow = $this->findPoolTeam($poolTeamId);
        if (!$poolTeamRow) {
            $poolTeamRow = [
                'poolTeamId' => $poolTeamId,
                'projectId' => $projectId,
                'poolKey' => $poolKey,
                'poolTypeKey' => $poolTypeKey,
                'poolTeamKey' => $poolTeamKey,
                'poolView' => $poolView,
                'poolSlotView' => $team['poolSlotView'],
                'poolTypeView' => $team['poolTypeKey'],
                'poolTeamView' => $poolTeamView,
                'poolTeamSlotView' => $poolTeamSlotView,
                'sourcePoolKeys' => null,
                'sourcePoolSlot' => null,
                'program' => $program,
                'gender' => $gender,
                'age' => $age,
                'division' => $division,
                'regTeamId' => $regTeamId,
                'regTeamName' => $regTeamName,
                'regTeamPoints' => 0,
            ];

            $this->poolTeamConn->insert('poolTeams', $poolTeamRow);
        } else {
            $poolTeamRow = [
                'regTeamId' => $regTeamId,
                'regTeamName' => $regTeamName,
                'regTeamPoints' => 0,
            ];

            $this->poolTeamConn->update(
                'poolTeams',
                $poolTeamRow,
                ['poolTeamId' => $poolTeamId, 'projectId' => $projectId]
            );
        }

        $this->results->updatedPoolTeams[] = $poolTeamRow;

    }
}