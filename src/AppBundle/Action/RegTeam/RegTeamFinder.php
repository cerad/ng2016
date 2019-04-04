<?php
namespace AppBundle\Action\RegTeam;

use AppBundle\Action\Game\GameFinderTrait;
use AppBundle\Action\Game\PoolTeam;
use AppBundle\Action\Game\RegTeam;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/* ==============================================
 * Last minute hacking
 * Copied from GameFinder
 */
class RegTeamFinder
{
    use GameFinderTrait;
    
    /** @var  Connection */
    private $gameConn;

    /** @var  Connection */
    private $regTeamConn;

    public function __construct(Connection $gameConn, Connection $regTeamConn)
    {
        $this->gameConn    = $gameConn;
        $this->regTeamConn = $regTeamConn;
    }
    /** =======================================================================
     * @param  array $criteria
     * @return RegTeam[]
     * @throws DBALException
     */
    public function findRegTeams(array $criteria)
    {
        $qb = $this->regTeamConn->createQueryBuilder();

        $qb->select('*')->from('regTeams')->orderBy('regTeamId');

        $whereMeta = [
            'regTeamIds'  => 'regTeamId',
            'projectIds'  => 'projectId',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $regTeams = [];
        while($regTeamRow = $stmt->fetch()) {
            $regTeams[$regTeamRow['regTeamId']] = RegTeam::createFromArray($regTeamRow);
        }
        if (count($regTeams) < 1) {
            return [];
        }
        // Join the pool keys, probably shoud use pool finder here
        $sql = 'SELECT * FROM poolTeams WHERE regTeamId IN (?) ORDER BY regTeamId,poolKey';
        $stmt = $this->gameConn->executeQuery($sql,[array_keys($regTeams)],[Connection::PARAM_STR_ARRAY]);
        while($row = $stmt->fetch()) {
            
            // Legacy stuff
            $regTeams[$row['regTeamId']]->addPoolKey($row['poolKey']);
            $regTeams[$row['regTeamId']]->addPoolTeamKey($row['poolTeamKey']);

            $poolTeam = PoolTeam::createFromArray($row);
            $regTeams[$row['regTeamId']]->addPoolTeam($poolTeam);
            
        }
        return array_values($regTeams);
    }
}
