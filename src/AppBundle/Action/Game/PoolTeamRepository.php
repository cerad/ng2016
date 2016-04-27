<?php
namespace AppBundle\Action\Game;

use Doctrine\DBAL\Connection;

class PoolTeamRepository
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param  $criteria array
     * @return PoolTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findBy(array $criteria)
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select('*');
        $qb->from('projectPoolTeams');
        $qb->orderBy('id');

        $values = [];
        $types  = [];

        $columns = [
            'id','projectKey',
            'poolTeamKey','poolKey','poolType',
            'program','gender','age','division',
        ];
        foreach($columns as $key) {

            $keys = $key . 's';

            if (isset($criteria[$keys]) && count($criteria[$keys])) {
                $qb->andWhere  ($key . ' IN (?)');
                $values[] = $criteria[$keys];
                $types[]  = Connection::PARAM_STR_ARRAY;
            }
        }

        $stmt = $this->conn->executeQuery($qb->getSQL(),$values,$types);
        $poolTeams = [];
        while($poolTeamRow = $stmt->fetch()) {
            $poolTeams[] = PoolTeam::fromArray($poolTeamRow);
        }
        return $poolTeams;
    }
    /** ===========================================================================
     * @param  PoolTeamId $poolTeamId
     * @return PoolTeam|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function find(PoolTeamId $poolTeamId)
    {
        // Load the poolTeam row
        $stmt = $this->conn->executeQuery('SELECT * FROM projectPoolTeams WHERE id = ?',[$poolTeamId]);
        $poolTeamRow  = $stmt->fetch();
        if (!$poolTeamRow) {
            return null;
        }
        $poolTeamArray = $poolTeamRow;

        // Done
        return PoolTeam::fromArray($poolTeamArray);
    }
    /** ==========================================================
     * @param  PoolTeam $poolTeam
     * @return PoolTeam
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save(PoolTeam $poolTeam)
    {
        $poolTeamArray = $poolTeam->toArray();

        // Pull the id
        $poolTeamId = $poolTeamArray['id'];
        
        // Does it exist (update/create)
        $stmt = $this->conn->executeQuery('SELECT id FROM projectPoolTeams WHERE id = ?',[$poolTeamId]);
        if ($stmt->fetch()) {
            unset($poolTeamArray['id']);
            $this->conn->update('projectPoolTeams',$poolTeamArray,[$poolTeamId]);
            $poolTeamArray['id'] = $poolTeamId;
        }
        else {
            $this->conn->insert('projectPoolTeams',$poolTeamArray);
        }
        
        // Done
        return PoolTeam::fromArray($poolTeamArray);
    }
}
