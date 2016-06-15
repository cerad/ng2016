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
    public function removeRegTeam($teamId)
    {
        // Composite key
        $sql = 'DELETE FROM regTeams WHERE teamId = ?';

        return; //$this->regTeamConn->executeUpdate($sql,[$teamId]);
    }

    /* ===========================================
     * Add RegPersonPerson
     */
    public function addRegTeam($teamId)
    {
        // Need the name for performance
        $sql = 'SELECT teamName,program,age,gender FROM regTeams WHERE regTeamId = ?';

        $stmt = $this->regTeamConn->executeQuery($sql,[$teamId]);
        $row = $stmt->fetch();
        if (!$row) return 0; // Should not happen

        // Messy need a view
        $data['teamName'] = sprintf('%s-%s %s',
            $row['age'],$row['gender'],$row['teamName']);

        //$this->regTeamConn->insert('regTeams',$data);

        return 1;
    }
}
