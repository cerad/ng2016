<?php
namespace AppBundle\Action\Results2019;

use AppBundle\Common\QueryBuilderTrait;

use Doctrine\DBAL\Connection;

class ResultsFinder
{
    use QueryBuilderTrait;

    /** @var Connection  */
    private $conn;
    
    /** @var ResultsStandingsCalculator  */
    private $standingsCalculator;
    
    public function __construct(
        Connection $conn,
        ResultsStandingsCalculator $standingsCalculator
    )
    {
        $this->conn = $conn;
        $this->standingsCalculator = $standingsCalculator;
    }

    /** =======================================================================
     * Maybe this should be just for one pool?
     * Should we use the standings calculator here?
     * 
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
            'poolSlotViews'=> 'poolSlotView',

            'programs'     => 'program',
            'genders'      => 'gender',
            'ages'         => 'age',
            'divisions'    => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        /** @var ResultsPool[] $pools */
        $pools = [];
        $poolTeamIds = [];
        while($poolTeamRow = $stmt->fetch()) {
            $poolKey = $poolTeamRow['poolKey'];
            if (!isset($pools[$poolKey])) {
                $pools[$poolKey] = ResultsPool::createFromArray($poolTeamRow);
            }
            $pool = $pools[$poolKey];
            $poolTeam = ResultsPoolTeam::createFromArray($poolTeamRow);
            $poolTeamIds[$poolTeam->poolTeamId] = $poolTeam;
            $pool->addPoolTeam($poolTeam);
        }
        $games = $this->findGames(array_keys($poolTeamIds));
        foreach($games as $game) {
            $homeTeamPoolKey = $game->homeTeam->poolKey;
            $pools[$homeTeamPoolKey]->addPoolGame($game);
            // Cross Pool
            $awayTeamPoolKey = $game->awayTeam->poolKey;
            if ($awayTeamPoolKey !== $homeTeamPoolKey) {
                $pools[$awayTeamPoolKey]->addPoolGame($game);
            }
        }
        // Apply standings?
        //$standingsCalculator = $this->standingsCalculator;
        //foreach($pools as $pool) {
        //    $poolTeams = $standingsCalculator($pool);
        //    $pool->setPoolTeams($poolTeams);
        //}
        return $pools;
    }

    /**
     * @param  array $poolTeamIds
     * @return ResultsGame[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function findGames(array $poolTeamIds)
    {
        if (count($poolTeamIds) < 1) {
            return [];
        }
        // Try with just one query
        $sql = <<<EOD
SELECT
  game.gameId,
  game.gameNumber,
  game.projectId,
  game.reportState,
  game.start,
  game.fieldName,
  
  gameTeam.gameTeamId,
  gameTeam.slot,
  gameTeam.results,
  gameTeam.pointsScored,
  gameTeam.pointsAllowed,
  gameTeam.pointsEarned,
  gameTeam.sportsmanship,
  gameTeam.misconduct,
  
  poolTeam.poolTeamId,
  poolTeam.poolKey,
  poolTeam.poolView,
  poolTeam.poolSlotView,
  poolTeam.poolTypeKey,
  poolTeam.poolTypeView,
  poolTeam.poolTeamKey,
  poolTeam.poolTeamSlotView,
  
  poolTeam.regTeamName
  
FROM gameTeams AS gameTeam
LEFT JOIN games AS game ON game.gameId = gameTeam.gameId
LEFT JOIN poolTeams AS poolTeam ON poolTeam.poolTeamId = gameTeam.poolTeamId
WHERE gameTeam.poolTeamId IN (?)
ORDER BY gameTeam.gameTeamId
EOD;
        $stmt = $this->conn->executeQuery($sql,[$poolTeamIds],[Connection::PARAM_STR_ARRAY]);
        $games = [];
        while($row = $stmt->fetch()) {
            $gameId = $row['gameId'];
            if (!isset($games[$gameId])) {
                $games[$gameId] = ResultsGame::createFromArray($row);
            }
            $game = $games[$gameId];
            $gameTeam = ResultsGameTeam::createFromArray($row);
            $game->addTeam($gameTeam);
        }
        return $games;
    }
}
