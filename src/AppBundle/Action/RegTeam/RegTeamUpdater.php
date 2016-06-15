<?php
namespace AppBundle\Action\RegTeam;

use Doctrine\DBAL\Connection;

class RegTeamUpdater
{
    private $regTeamConn;
    
    public function __construct(
        Connection $regTeamConn
    ) {
        $this->regTeamConn   = $regTeamConn;
    }
    /* ===========================================
     * Remove RegTeam
     */
    public function removeRegTeam($teamKey)
    {
        // Composite key
        $sql = 'DELETE FROM regTeams WHERE teamKey = ?';

        return; //$this->regTeamConn->executeUpdate($sql,[$teamKey]);
    }

    /* ===========================================
     * Add RegTeam
     */
    public function addRegTeam($teamKey)
    {
        // Need the name for performance
        $sql = 'SELECT regTeamId,projectId,teamKey,teamNumber,teamName,teamPoints,orgId,orgView,program,gender,age,division FROM regTeams WHERE teamKey = ?';

        $stmt = $this->regTeamConn->executeQuery($sql,[$regTeamId]);
        $row = $stmt->fetch();
        if (!$row) return 0; // Should not happen
        
        $orgKey = 'AYSOR';
        $orgId = str_pad($row['orgId'],4,'0',STR_PAD_LEFT);

        $data['teamName'] = $row['teamName'];
        $data['orgId'] = $orgId;
        $data['orgView'] = $row['orgView'];

        //$this->regTeamConn->insert('regTeams',$data);
        return 1;
    }
    /* ===========================================
     * Update RegTeam teamName
     */
    public function updateRegTeamName($projId, $teamKey, $teamName)
    {
        // sql update
        $sql = 'UPDATE regTeams SET teamName = ? WHERE regTeamId = ?';
        
//        $sql = "
//            SELECT ?
//            FROM ng2016games.regTeams
//            WHERE teamKey = ?
//        ";
//        
//        $params = array('teamName', 'U10B-Core-01');
//        $stmt = $this->regTeamConn->executeQuery($sql, $params);
//        $row = $stmt->fetch();
//var_dump($row);
//die();

        $regTeamId = $projId . ':' . $teamKey;
        $params = array($teamName, $regTeamId);
     
        $stmt = $this->regTeamConn->executeQuery($sql, $params);

        return 1;
    }
   /* ===========================================
     * Update RegTeam SAR
     */
    public function updateRegTeamSAR($projId, $teamKey, $sars)
    {
        // sql update
        $sql = "
            UPDATE regTeams 
            SET orgId=?, orgView=?
            WHERE regTeamId = ?
        ";
        
        $regTeamId = $projId . ':' . $teamKey;

        $region = explode('-',$sars)[2];
        $orgKey = 'AYSOR:';
        $orgId = $orgKey . str_pad($region,4,'0',STR_PAD_LEFT);

        $params = array($orgId, $sars, $regTeamId);      
      
        $stmt = $this->regTeamConn->executeQuery($sql, $params);

        return 1;
    }
}
