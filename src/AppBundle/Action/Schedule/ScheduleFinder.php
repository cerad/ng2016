<?php

namespace AppBundle\Action\Schedule;

use AppBundle\Action\RegPerson\RegPersonFinder;
use AppBundle\Common\QueryBuilderTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL;

class ScheduleFinder
{
    use QueryBuilderTrait;

    /** @var  Connection */
    private $gameConn;

    /** @var  Connection */
    private $regTeamConn;

    /** @var RegPersonFinder */
    private $regPersonFinder;

    /**
     * ScheduleFinder constructor.
     * @param Connection $gameConn
     * @param Connection $regTeamConn
     * @param RegPersonFinder $regPersonFinder
     */
    public function __construct(
        Connection $gameConn,
        Connection $regTeamConn,
        RegPersonFinder $regPersonFinder
    ) {
        $this->gameConn = $gameConn;
        $this->regTeamConn = $regTeamConn;
        $this->regPersonFinder = $regPersonFinder;
    }

    /**
     * @param array $criteria
     * @param bool $objects
     * @return array|mixed
     * @throws DBAL\DBALException
     */
    public function findGames(array $criteria, $objects = true)
    {
        // Basic query
        $gameIds = $this->findGameIds($criteria);

        // Add in reg person games
        $gameIds = array_merge($gameIds, $this->findGameIdsForRegPerson($criteria));

        // Amd move forward
        $gameIds = array_values($gameIds);

        // Load the games
        $games = $this->findGamesForIds($gameIds);

        // Load the teams
        $wantTeams = isset($criteria['wantTeams']) ? $criteria['wantTeams'] : true;
        if ($wantTeams) {
            $games = $this->joinTeamsToGames($games);
        }
        // Load the officials
        $wantOfficials = isset($criteria['wantOfficials']) ? $criteria['wantOfficials'] : true;
        if ($wantOfficials) {
            $games = $this->joinOfficialsToGames($games);
        }
        // Convert to objects
        if (!$objects) {
            return $games;
        }
        $gameObjects = [];
        foreach ($games as $game) {
            $gameObjects[] = ScheduleGame::createFromArray($game);
        }
        if (isset($criteria['sortBy'])) {
            $gameObjects = $this->sortGames($gameObjects, $criteria['sortBy']);
        }

        // Done
        return $gameObjects;
    }

    /**
     * @param array $games
     * @return array
     * @throws DBAL\DBALException
     */
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
  
  gameTeam.poolTeamId,
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
        $stmt = $this->gameConn->executeQuery($sql, [array_keys($games)], [Connection::PARAM_STR_ARRAY]);
        while ($gameTeam = $stmt->fetch()) {
            $gameId = $gameTeam['gameId'];
            $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;
        }

        return $games;
    }

    /**
     * @param array $games
     * @return array
     * @throws DBAL\DBALException
     */
    private function joinOfficialsToGames(array $games)
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
        $stmt = $this->gameConn->executeQuery($sql, [array_keys($games)], [Connection::PARAM_STR_ARRAY]);
        while ($gameOfficial = $stmt->fetch()) {
            $gameId = $gameOfficial['gameId'];
            $games[$gameId]['officials'][$gameOfficial['slot']] = $gameOfficial;
        }

        return $games;
    }

    /**
     * @param $gameIds
     * @return array
     * @throws DBAL\DBALException
     */
    private function findGamesForIds($gameIds)
    {
        if (!count($gameIds)) {
            return [];
        }
        $sql = 'SELECT * FROM games WHERE gameId IN (?) ORDER BY gameNumber';
        $stmt = $this->gameConn->executeQuery($sql, [$gameIds], [Connection::PARAM_STR_ARRAY]);
        $games = [];
        while ($game = $stmt->fetch()) {
            $game['teams'] = [];
            $game['officials'] = [];
            $game['gameNumber'] = (integer)$game['gameNumber'];
            $games[$game['gameId']] = $game;
        }

        return $games;
    }

    /**
     * @param array $criteria
     * @return array
     * @throws DBAL\DBALException
     */
    private function findGameIds(array $criteria)
    {
        // Very much a hack, ignore most of the criteria for my schedule
        $doGeneral = isset($criteria['doGeneral']) ? $criteria['doGeneral'] : true;
        if (!$doGeneral) {
            return [];
        }
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
            'poolTeamIds' => 'gameTeam.poolTeamId', // TEST!
            'regTeamIds'  => 'regTeamId',
        ];
        // update criteria & sql to allow for Club ages like '2005/2006'
        if(!empty($criteria['ages'])) {
            $criteria['ages'] = isset($criteria['ages']) ? array(implode('|', $criteria['ages'])) : array();
        }
        list($values, $types) = $this->addWhere($qb, $whereMeta, $criteria);
        if (!count($values)) {
            //return [];
        }
        if(!empty($criteria['ages'])) {
            $sql = str_replace("age IN (?)", "age REGEXP (?)", $qb->getSQL());
        } else {
            $sql = $qb->getSQL();
        }

        $stmt = $qb->getConnection()->executeQuery($sql,$values,$types);
        $gameIds = [];
        while ($gameId = $stmt->fetch()) {
            $gameIds[$gameId['gameId']] = $gameId['gameId'];
        }

        return $gameIds; // Indexed to allow merging multiple queries
    }

    /* ==========================================
     * Little bit hackish but want to query all games
     * That impact a particular person
     * Need an or condition
     */
    /**
     * @param array $criteria
     * @return array
     * @throws DBAL\DBALException
     */
    private function findGameIdsForRegPerson(array $criteria)
    {
        // Make sure have a person
        $regPersonId = isset($criteria['regPersonId']) ? $criteria['regPersonId'] : null;
        if (!$regPersonId) {
            return [];
        }
        // Get the crew
        $criteria['regPersonIds'] = $this->regPersonFinder->findRegPersonPersonIds($regPersonId);

        // Get ids for game officials
        $qb = $this->gameConn->createQueryBuilder();
        $qb->select('DISTINCT game.gameId');
        $qb->from('games', 'game');
        $qb->leftJoin('game', 'gameOfficials', 'gameOfficial', 'gameOfficial.gameId = game.gameId');
        $qb->orderBy('game.gameId');

        // Suffice for now, might want to add some of the criteria back in later
        $whereMeta = [
            'projectIds' => 'game.projectId',
            'dates' => 'DATE(game.start)',
            'regPersonIds' => 'gameOfficial.regPersonId',
        ];
        list($values, $types) = $this->addWhere($qb, $whereMeta, $criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(), $values, $types);
        $gameIds = [];
        while ($gameId = $stmt->fetch()) {
            $gameIds[$gameId['gameId']] = $gameId['gameId'];
        }

        // Now do a query for any related teams
        $regTeamIds = $this->regPersonFinder->findRegPersonTeamIds($regPersonId);
        if (count($regTeamIds) < 1) {
            return $gameIds;
        }
        $criteria['regTeamIds'] = $regTeamIds;

        // Get ids for registered teams
        $qb = $this->gameConn->createQueryBuilder();
        $qb->select('DISTINCT game.gameId');
        $qb->from('games', 'game');
        $qb->leftJoin('game', 'gameTeams', 'gameTeam', 'gameTeam.gameId = game.gameId');
        $qb->leftJoin('gameTeam', 'poolTeams', 'poolTeam', 'poolTeam.poolTeamId = gameTeam.poolTeamId');
        $qb->orderBy('game.gameId');

        // Suffice for now, might want to add some of the criteria back in later
        $whereMeta = [
            'projectIds' => 'game.projectId',
            'dates' => 'DATE(game.start)',
            'regTeamIds' => 'poolTeam.regTeamId',
        ];
        list($values, $types) = $this->addWhere($qb, $whereMeta, $criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(), $values, $types);
        while ($gameId = $stmt->fetch()) {
            $gameIds[$gameId['gameId']] = $gameId['gameId'];
        }

        return $gameIds;
    }

    /* =======================================================================
     * Sort after the load to avoid joins
     * Plus it is a bit more flexible and readable
     */
    const SORT_BY_START_POOL_FIELD = 1;
    const SORT_BY_VENUE_FIELD_START = 2;
    const SORT_BY_DATE_FIELD_TIME = 3;
    const SORT_BY_GROUP_DATE_TIME = 4;
    const SORT_BY_PROJECT_GAME_NUMBER = 5;

    /**
     * @param $games
     * @param $sortBy
     * @return mixed
     */
    protected function sortGames($games, $sortBy)
    {
        if ($sortBy === self::SORT_BY_START_POOL_FIELD) {
            usort(
                $games,
                function (ScheduleGame $game1, ScheduleGame $game2) {

                    if ($game1->start > $game2->start) {
                        return 1;
                    }
                    if ($game1->start < $game2->start) {
                        return -1;
                    }

                    $game1Div = substr($game1->poolView, 0,4);
                    $game2Div = substr($game2->poolView, 0,4);

                    if ($game1Div > $game2Div) {
                        return 1;
                    }
                    if ($game1Div < $game2Div) {
                        return -1;
                    }

                    if ($game1->fieldName > $game2->fieldName) {
                        return 1;
                    }
                    if ($game1->fieldName < $game2->fieldName) {
                        return -1;
                    }

                    return 0;
                }
            );

            return $games;
        }
        if ($sortBy === self::SORT_BY_DATE_FIELD_TIME) {
            usort(
                $games,
                function (ScheduleGame $game1, ScheduleGame $game2) {

                    $date1 = substr($game1->start, 0, 10);
                    $date2 = substr($game1->start, 0, 10);
                    if ($date1 > $date2) {
                        return 1;
                    }
                    if ($date1 < $date2) {
                        return -1;
                    }

                    if ($game1->fieldName > $game2->fieldName) {
                        return 1;
                    }
                    if ($game1->fieldName < $game2->fieldName) {
                        return -1;
                    }

                    $time1 = substr($game1->start, 11); // 2016-07-07 08:00:00
                    $time2 = substr($game2->start, 11);
                    if ($time1 > $time2) {
                        return 1;
                    }
                    if ($time1 < $time2) {
                        return -1;
                    }

                    return 0;
                }
            );

            return $games;
        }
        if ($sortBy === self::SORT_BY_VENUE_FIELD_START) {
            usort(
                $games,
                function (ScheduleGame $game1, ScheduleGame $game2) {

                    if ($game1->venueName > $game2->venueName) {
                        return 1;
                    }
                    if ($game1->venueName < $game2->venueName) {
                        return -1;
                    }

                    if ($game1->fieldName > $game2->fieldName) {
                        return 1;
                    }
                    if ($game1->fieldName < $game2->fieldName) {
                        return -1;
                    }

                    if ($game1->start > $game2->start) {
                        return 1;
                    }
                    if ($game1->start < $game2->start) {
                        return -1;
                    }

                    return 0;
                }
            );

            return $games;
        }
        if ($sortBy === self::SORT_BY_GROUP_DATE_TIME) {
            usort(
                $games,
                function (ScheduleGame $game1, ScheduleGame $game2) {

                    $game1Div = substr($game1->poolView, 0,4);
                    $game2Div = substr($game2->poolView, 0,4);

                    if ($game1Div > $game2Div) {
                        return 1;
                    }
                    if ($game1Div < $game2Div) {
                        return -1;
                    }

                    if ($game1->start > $game2->start) {
                        return 1;
                    }
                    if ($game1->start < $game2->start) {
                        return -1;
                    }

                    if ($game1->fieldName > $game2->fieldName) {
                        return 1;
                    }
                    if ($game1->fieldName < $game2->fieldName) {
                        return -1;
                    }

                    return 0;
                }
            );

            return $games;
        }
        if ($sortBy === self::SORT_BY_PROJECT_GAME_NUMBER) {
            usort(
                $games,
                function (ScheduleGame $game1, ScheduleGame $game2) {

                    if ($game1->projectId > $game2->projectId) {
                        return 1;
                    }
                    if ($game1->projectId < $game2->projectId) {
                        return -1;
                    }

                    if ($game1->gameNumber > $game2->gameNumber) {
                        return 1;
                    }
                    if ($game1->gameNumber < $game2->gameNumber) {
                        return -1;
                    }

                    return 0;
                }
            );

            return $games;
        }

        return $games;
    }

    /** ===========================================================================
     * For the team schedule choices, maybe just make it choices
     *
     * @param  array $criteria
     * @param  bool $objects
     * @return ScheduleRegTeam[]|array
     * @throws DBAL\DBALException
     */
    public function findRegTeams(array $criteria, $objects = true)
    {
        $qb = $this->regTeamConn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('regTeamId,teamName,division')->from('regTeams')->orderBy('regTeamId');

        $whereMeta = [
            'regTeamIds' => 'id',
            'projectIds' => 'projectId',
            'programs' => 'program',
            'genders' => 'gender',
            'ages' => 'age',
            'divisions' => 'division',
        ];
        list($values, $types) = $this->addWhere($qb, $whereMeta, $criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(), $values, $types);
        $regTeams = [];
        while ($regTeam = $stmt->fetch()) {
            $regTeams[$regTeam['regTeamId']] = $objects ? ScheduleRegTeam::createFromArray($regTeam) : $regTeam;
        }

        // Keep the object flag for now though I don't think it is needed
        return $objects ? array_values($regTeams) : $regTeams;
    }
}