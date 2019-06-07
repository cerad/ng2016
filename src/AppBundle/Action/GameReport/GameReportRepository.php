<?php
namespace AppBundle\Action\GameReport;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class GameReportRepository
{
    /** @var  Connection */
    private $gameConn;
    
    public function __construct(Connection $gameConn)
    {
        $this->gameConn = $gameConn;
    }

    public function updateGameReport(GameReport $gameReport)
    {
        // Update the game (maybe move to gameReport entity)
        $gameReportRow = $gameReport->toUpdateArray();

        // Stash the id
        $gameId = $gameReportRow['gameId'];
        unset($gameReportRow['gameId']);

        // Stash the teams
        $gameReportTeamRows = $gameReportRow['teams'];
        unset($gameReportRow['teams']);

        // Update
        $this->gameConn->update('games',$gameReportRow,['gameId' => $gameId]);
        
        // Update the teams
        foreach($gameReportTeamRows as $gameReportTeamRow) {

            // Stash the id
            $gameTeamId = $gameReportTeamRow['gameTeamId'];
            unset($gameReportTeamRow['gameTeamId']);

            // Misconduct
            $gameReportTeamRow['misconduct'] = count($gameReportTeamRow['misconduct']) ? serialize($gameReportTeamRow['misconduct']) : null;

            // Update
            $this->gameConn->update('gameTeams',$gameReportTeamRow,['gameTeamId' => $gameTeamId]);
        }
    }
    /** =======================================================================
     * @param  $projectId
     * @param  $gameNumber
     * @return GameReport|null
     * @throws DBALException
     */
    public function findGameReport($projectId,$gameNumber)
    {
        // Load the game
        $sql  = 'SELECT * FROM games WHERE projectId = ? AND gameNumber = ?';
        $stmt = $this->gameConn->executeQuery($sql,[$projectId,$gameNumber]);
        $game = $stmt->fetch();
        if (!$game) {
            return null;
        }
        // Load the teams
        $sql = <<<EOD
SELECT 
  gameTeam.*,  -- To get all the scors and such
  
  poolTeam.poolKey,
  poolTeam.poolTypeKey,
  poolTeam.poolTeamKey,
  poolTeam.poolView,
  poolTeam.poolTypeView,
  poolTeam.poolTeamView,
  poolTeam.poolTeamSlotView,
  
  poolTeam.regTeamName,
  poolTeam.regTeamPoints
  
FROM      gameTeams AS gameTeam
LEFT JOIN poolTeams AS poolTeam ON poolTeam.poolTeamId = gameTeam.poolTeamId
WHERE gameTeam.gameId = ?
ORDER BY gameNumber,slot
EOD;
        $stmt = $this->gameConn->executeQuery($sql,[$game['gameId']]);
        while($gameTeam = $stmt->fetch()) {
            $gameTeam['misconduct'] = isset($gameTeam['misconduct']) ? unserialize($gameTeam['misconduct']) : [];
            $game['teams'][$gameTeam['slot']] = $gameTeam;
        }
        return GameReport::createFromArray($game);
    }
    public function doesGameExist($projectId,$gameNumber)
    {
        // Load the game
        $sql = 'SELECT gameId FROM games WHERE projectId = ? AND gameNumber = ?';
        $stmt = $this->gameConn->executeQuery($sql, [$projectId, $gameNumber]);
        $game = $stmt->fetch();
        return $game ? true : false;
    }
}