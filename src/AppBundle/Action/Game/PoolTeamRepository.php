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

        $criteria['ids'] = isset($criteria['poolTeamIds']) ? $criteria['poolTeamIds'] : null;

        $columns = [
            'ids','projectKey',
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
            $poolTeams[] = PoolTeam::createFromArray($poolTeamRow);
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
        return PoolTeam::createFromArray($poolTeamArray);
    }
    /** ==========================================================
     * @param  PoolTeam $poolTeam
     * @return PoolTeam
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save(PoolTeam $poolTeam)
    {
        $poolTeamArray = $poolTeam->toArray();

        // Does it exist (update/create)
        $poolTeamId  = $poolTeamArray['id'];
        $stmt = $this->conn->executeQuery('SELECT id FROM projectPoolTeams WHERE id = ?',[$poolTeamId]);
        if (!$stmt->fetch()) {
            $this->conn->insert('projectPoolTeams', $poolTeamArray);
        }
        else {
            // These are value objects, do we really want to update?
            // Better to delete then reinsert
            // Especially without foriegn key constraints
            $poolTeamId  = $poolTeamArray['id'];
            $projectKey  = $poolTeamArray['projectKey'];
            $poolTeamKey = $poolTeamArray['poolTeamKey'];

            unset($poolTeamArray['id']);
            unset($poolTeamArray['projectKey']);
            unset($poolTeamArray['poolTeamKey']);

            $this->conn->update('projectPoolTeams',$poolTeamArray,['id' => $poolTeamId]);

            $poolTeamArray['id']          = $poolTeamId;
            $poolTeamArray['projectKey']  = $projectKey;
            $poolTeamArray['poolTeamKey'] = $poolTeamKey;
        }

        // Done
        return PoolTeam::createFromArray($poolTeamArray);
    }
}
