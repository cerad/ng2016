<?php
namespace AppBundle\Action\GameReport2016;

use Doctrine\DBAL\Connection;

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
    /**
     * @param  $projectId
     * @param  $gameNumber
     * @return GameReport|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findGameReport($projectId,$gameNumber)
    {
        // Load the game
        $gameId = $projectId . ':' . $gameNumber;
        $sql  = 'SELECT * FROM games WHERE gameId = ?';
        $stmt = $this->gameConn->executeQuery($sql,[$gameId]);
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
  
  poolTeam.regTeamName,
  poolTeam.regTeamPoints
  
FROM      gameTeams AS gameTeam
LEFT JOIN poolTeams AS poolTeam ON poolTeam.poolTeamId = gameTeam.poolTeamId
WHERE gameTeam.gameId = ?
ORDER BY gameNumber,slot
EOD;
        $stmt = $this->gameConn->executeQuery($sql,[$gameId]);
        while($gameTeam = $stmt->fetch()) {
            // Turn all the scores to integers?
            $gameTeam['misconduct'] = isset($gameTeam['misconduct']) ? unserialize($gameTeam['misconduct']) : [];
            $game['teams'][$gameTeam['slot']] = $gameTeam;
        }
        return GameReport::createFromArray($game);
    }
}