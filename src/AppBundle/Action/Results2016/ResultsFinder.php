<?php
namespace AppBundle\Action\Results2016;

use AppBundle\Common\QueryBuilderTrait;

use Doctrine\DBAL\Connection;

class ResultsFinder
{
    use QueryBuilderTrait;

    /** @var Connection  */
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param  $criteria array
     * @return ResultsPool[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findPools($criteria)
    {
        // Grab the pools
        $qb = $this->conn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*')->from('poolTeams')->orderBy('poolKey,poolTeamKey');

        $whereMeta = [
            'projectIds'   => 'projectId',

            'poolKeys'     => 'poolKey',
            'poolTypeKeys' => 'poolTypeKey',
            'poolTeamKeys' => 'poolTeamKey',
            'poolTeamIds'  => 'poolTeamId',

            'programs'     => 'program',
            'genders'      => 'gender',
            'ages'         => 'age',
            'divisions'    => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        /** @var ResultsPool[] $pools */
        $pools = [];
        $poolTeams = [];
        while($poolTeamRow = $stmt->fetch()) {
            $poolKey = $poolTeamRow['poolKey'];
            if (!isset($pools[$poolKey])) {
                $pools[$poolKey] = ResultsPool::createFromArray($poolTeamRow);
            }
            $pool = $pools[$poolKey];
            $poolTeam = ResultsPoolTeam::createFromArray($poolTeamRow);
            $poolTeams[$poolTeam->poolTeamId] = $poolTeam;
            $pool->addPoolTeam($poolTeam);
        }
        return $pools;
    }
}