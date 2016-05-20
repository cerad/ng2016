<?php
namespace AppBundle\Action\Game;

use AppBundle\Common\QueryBuilderTrait;
use Doctrine\DBAL\Connection;

/* ============================================================================
 * Not sure how well this will work out
 * But try combining some common methods
 * These are all array based so it might work out okay
 */
trait GameFinderTrait
{
    use QueryBuilderTrait;
    
    private function joinTeamsToGames(Connection $conn, array $games)
    {
        if (!count($games)) {
            return [];
        }
        $sql = <<<EOD
SELECT 

  gameTeam.gameTeamId,
  gameTeam.gameId,
  gameTeam.gameNumber,
  gameTeam.slot,
  
  poolTeam.regTeamId,
  poolTeam.regTeamName,
  poolTeam.division,
  
  poolTeam.poolTeamKey,
  
  poolTeam.poolView,
  poolTeam.poolTypeView,
  poolTeam.poolTeamView,
  poolTeam.poolTeamSlotView
  
FROM      gameTeams AS gameTeam
LEFT JOIN poolTeams AS poolTeam ON poolTeam.poolTeamId = gameTeam.poolTeamId
WHERE gameTeam.gameId IN (?)
ORDER BY gameNumber,slot
EOD;
        $stmt = $conn->executeQuery($sql,[array_keys($games)],[Connection::PARAM_STR_ARRAY]);
        while($gameTeam = $stmt->fetch()) {
            $gameId = $gameTeam['gameId'];
            $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;
        }
        return $games;
    }
    private function joinOfficialsToGames(Connection $conn, array $games)
    {
        if (!count($games)) {
            return [];
        }
        $sql = <<<EOD
SELECT * 
FROM  gameOfficials AS gameOfficial
WHERE gameOfficial.gameId IN (?)
ORDER BY gameNumber,slot
EOD;
        $stmt = $conn->executeQuery($sql,[array_keys($games)],[Connection::PARAM_STR_ARRAY]);
        while($gameOfficial = $stmt->fetch()) {
            $gameId = $gameOfficial['gameId'];
            $games[$gameId]['officials'][$gameOfficial['slot']] = $gameOfficial;
        }
        return $games;
    }

    protected function findGamesForIds(Connection $conn, $gameIds)
    {
        if (!count($gameIds)) {
            return [];
        }
        $sql = 'SELECT * FROM games WHERE gameId IN (?) ORDER BY gameNumber';
        $stmt = $conn->executeQuery($sql,[$gameIds],[Connection::PARAM_STR_ARRAY]);
        $games = [];
        while($game = $stmt->fetch()) {
            $game['teams']     = [];
            $game['officials'] = [];
            $game['gameNumber'] = (integer)$game['gameNumber'];
            $games[$game['gameId']] = $game;
        }
        return $games;
    }

    protected function findGameIds(Connection $conn, array $criteria)
    {
        $qb = $conn->createQueryBuilder();
        $qb->select('DISTINCT game.gameId');
        $qb->from('games','game');
        $qb->leftJoin('game',    'gameTeams','gameTeam','gameTeam.gameId = game.gameId');
        $qb->leftJoin('gameTeam','poolTeams','poolTeam','poolTeam.poolTeamId = gameTeam.poolTeamId');
        $qb->orderBy ('game.gameId');

        $whereMeta = [
            'projectIds'  => 'game.projectId',
            'gameNumbers' => 'game.gameNumber',
            'dates'       => 'DATE(game.start)',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
            'poolTypes'   => 'poolTypeKey',
            'poolTeamIds' => 'poolTeam.poolTeamId',
            'regTeamIds'  => 'regTeamId',
        ];

        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        if (!count($values)) {
            //return [];
        }
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $gameIds = [];
        while($gameId = $stmt->fetch()) {
            $gameIds[] = $gameId['gameId'];
        }
        return $gameIds;
    }
}
