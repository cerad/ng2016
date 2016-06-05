<?php
namespace AppBundle\Action\RegPerson;

use Doctrine\DBAL\Connection;

class RegPersonUpdater
{
    private $regPersonConn;
    
    public function __construct(
        Connection $regPersonConn
    ) {
        $this->regPersonConn = $regPersonConn;
    }
    /* ===========================================
     * Remove RegPersonPerson
     *
     */
    public function removeRegPersonPerson($role,$managerId,$memberId)
    {
        // For now, never remove Primary
        if ($role === 'Primary') {
            return 0;
        }
        // Composite key
        $sql = 'DELETE FROM regPersonPersons WHERE managerId = ? AND memberId = ? AND role = ?';

        return $this->regPersonConn->executeUpdate($sql,[$managerId,$memberId,$role]);
    }
    /* ===========================================
     * Add RegPersonPerson
     */
    public function addRegPersonPerson($role,$managerId,$memberId)
    {
        // For now, never insert Primary
        if ($role === 'Primary') {
            return 0;
        }
        // We avoid adding the same record twice
        $sql = 'SELECT role FROM regPersonPersons WHERE managerId = ? AND memberId = ? AND role = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$managerId,$memberId,$role]);
        if ($stmt->fetch()) {
            return 0;
        }
        $data = [
            'role' => $role,
            'managerId' => $managerId,
            'memberId'  => $memberId,
        ];
        // Need the names until the id's are cleared up
        $sql = 'SELECT name FROM projectPersons WHERE projectKey = ? AND personKey = ?';
        list($projectId,$phyPersonId) = explode(':',$managerId);
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
        $row = $stmt->fetch();dump($row);
        if (!$row) return 0; // Should not happen
        $data['managerName'] = $row['name'];

        list($projectId,$phyPersonId) = explode(':',$memberId);
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
        $row = $stmt->fetch();
        if (!$row) return 0; // Should not happen
        $data['memberName'] = $row['name'];

        $this->regPersonConn->insert('regPersonPersons',$data);

        return 1;
    }
}