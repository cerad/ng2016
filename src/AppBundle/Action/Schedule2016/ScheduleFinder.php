<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Common\QueryBuilderTrait;
use Doctrine\DBAL\Connection;

class ScheduleFinder
{
    use QueryBuilderTrait;

    /** @var  Connection */
    private $gameConn;

    /** @var  Connection */
    private $regTeamConn;

    public function __construct(Connection $gameConn, Connection $regTeamConn)
    {
        $this->gameConn    = $gameConn;
        $this->regTeamConn = $regTeamConn;
    }

    /**
     * @param  array $criteria
     * @param  bool $objects
     * @return ScheduleGame[]
     */
    public function findGames(array $criteria, $objects = true)
    {
        // Now find unique game ids
        $gameIds = $this->findGameIds($criteria);

        // Load the games
        $games = $this->findGamesForIds($gameIds);

        // Load the teams
        $games = $this->joinTeamsToGames($games);

        // Convert to objects
        if (!$objects) {
            return $games;
        }
        $gameObjects = [];
        foreach($games as $game) {
            $gameObjects[] = ScheduleGame::createFromArray($game);
        }
        if (isset($criteria['sortBy'])) {
            $gameObjects = $this->sortGames($gameObjects,$criteria['sortBy']);
        }
        // Done
        return $gameObjects;
    }
    private function joinTeamsToGames(array $games)
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
  
  poolTeam.poolView,
  poolTeam.poolTypeView,
  poolTeam.poolTeamView,
  poolTeam.poolTeamSlotView
  
FROM      gameTeams AS gameTeam
LEFT JOIN poolTeams AS poolTeam ON poolTeam.poolTeamId = gameTeam.poolTeamId
WHERE gameTeam.gameId IN (?)
ORDER BY gameNumber,slot
EOD;
        $stmt = $this->gameConn->executeQuery($sql,[array_keys($games)],[Connection::PARAM_STR_ARRAY]);
        while($gameTeam = $stmt->fetch()) {

            $gameId = $gameTeam['gameId'];
            $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;
        }
        return $games;
    }
    private function findGamesForIds($gameIds)
    {
        if (!count($gameIds)) {
            return [];
        }
        $sql = 'SELECT * FROM games WHERE gameId IN (?) ORDER BY gameNumber';
        $stmt = $this->gameConn->executeQuery($sql,[$gameIds],[Connection::PARAM_STR_ARRAY]);
        $games = [];
        while($game = $stmt->fetch()) {
            $game['teams'] = [];
            $game['gameNumber'] = (integer)$game['gameNumber'];
            $games[$game['gameId']] = $game;
        }
        return $games;
    }
    private function findGameIds(array $criteria)
    {
        $qb = $this->gameConn->createQueryBuilder();
        $qb->select('DISTINCT game.gameId');
        $qb->from('games','game');
        $qb->leftJoin('game',    'gameTeams','gameTeam','gameTeam.gameId = game.gameId');
        $qb->leftJoin('gameTeam','poolTeams','poolTeam','poolTeam.poolTeamId = gameTeam.poolTeamId');
        $qb->orderBy ('game.gameId');

        $whereMeta = [
            'projectIds'  => 'game.projectId',
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
    
    /* =======================================================================
     * Sort after the load to avoid joins
     * Plus it is a bit more flexible and readable
     */
    protected function sortGames($games,$sortBy)
    {
        if ($sortBy === 1) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                if ($game1->start > $game2->start) return  1;
                if ($game1->start < $game2->start) return -1;

                if ($game1->poolView > $game2->poolView) return  1;
                if ($game1->poolView < $game2->poolView) return -1;

                if ($game1->fieldName > $game2->fieldName) return  1;
                if ($game1->fieldName < $game2->fieldName) return -1;

                return 0;
            });
            return $games;
        }
        if ($sortBy === 2) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                $date1 = substr($game1->start,0,10);
                $date2 = substr($game1->start,0,10);
                if ($date1 > $date2) return  1;
                if ($date1 < $date2) return -1;

                if ($game1->fieldName > $game2->fieldName) return  1;
                if ($game1->fieldName < $game2->fieldName) return -1;

                $time1 = substr($game1->start,11); // 2016-07-07 08:00:00
                $time2 = substr($game2->start,11);
                if ($time1 > $time2) return  1;
                if ($time1 < $time2) return -1;

                return 0;
            });
            return $games;
        }
        if ($sortBy === 3) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                if ($game1->venueName > $game2->venueName) return  1;
                if ($game1->venueName < $game2->venueName) return -1;

                if ($game1->fieldName > $game2->fieldName) return  1;
                if ($game1->fieldName < $game2->fieldName) return -1;

                if ($game1->start > $game2->start) return  1;
                if ($game1->start < $game2->start) return -1;

                return 0;
            });
            return $games;
        }
        if ($sortBy === 4) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                if ($game1->projectId > $game2->projectId) return  1;
                if ($game1->projectId < $game2->projectId) return -1;

                if ($game1->gameNumber > $game2->gameNumber) return  1;
                if ($game1->gameNumber < $game2->gameNumber) return -1;

                return 0;
            });
            return $games;
        }
        return $games;
    }

    /** ===========================================================================
     * For the team schedule choices, maybe just make it choices
     *
     * @param  array $criteria
     * @param  bool  $objects
     * @return ScheduleRegTeam[]|array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegTeams(array $criteria, $objects = true)
    {
        $qb = $this->regTeamConn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('regTeamId,teamName,division')->from('regTeams')->orderBy('regTeamId');

        $whereMeta = [
            'regTeamIds'  => 'id',
            'projectIds'  => 'projectId',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $regTeams = [];
        while($regTeam = $stmt->fetch()) {
            $regTeams[$regTeam['regTeamId']] = $objects ? ScheduleRegTeam::createFromArray($regTeam) : $regTeam;
        }
        // Keep the object flag for now though I don't think it is needed
        return $objects ? array_values($regTeams) : $regTeams;
    }
}