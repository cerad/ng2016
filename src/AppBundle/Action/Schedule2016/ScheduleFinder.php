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
    private $poolConn;
    
    /** @var  Connection */
    private $teamConn;

    public function __construct(
        Connection $gameConn, 
        Connection $poolConn,
        Connection $teamConn)
    {
        $this->gameConn = $gameConn;
        $this->poolConn = $poolConn;
        $this->teamConn = $teamConn;
    }

    /**
     * @param  array $criteria
     * @param  bool $objects
     * @return ScheduleGame[]
     */
    public function findGames(array $criteria, $objects = true)
    {
        // First we need pool teams
        $poolTeamIds = $this->findPoolTeamIds($criteria);

        $criteria['poolTeamIds'] = $poolTeamIds;

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
            $gameObjects[] = ScheduleGame::fromArray($game);
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
        // Add game teams
        $sql  = 'SELECT * FROM projectGameTeams WHERE gameId IN (?)';
        $stmt = $this->gameConn->executeQuery($sql,[array_keys($games)],[Connection::PARAM_STR_ARRAY]);
        $poolTeamIds = [];
        while($gameTeam = $stmt->fetch()) {

            $gameTeam['name'] = null; // Should come from regTeam

            $gameId = $gameTeam['gameId'];
            $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;

            $poolTeamId = $gameTeam['poolTeamId'];
            $poolTeamIds[$poolTeamId][] = $gameTeam;
        }
        // Merge pool teams
        $poolTeams = $this->findPoolTeams(['poolTeamIds' => array_keys($poolTeamIds)],false);
        foreach($poolTeamIds as $poolTeamId => $gameTeamLinks) {
            foreach($gameTeamLinks as $gameTeam) {
                $poolTeam = $poolTeams[$poolTeamId];
                unset($poolTeam['id']);
                unset($poolTeam['projectKey']);
                $gameTeam = array_replace($gameTeam,$poolTeam);
                $gameId = $gameTeam['gameId'];
                $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;
            }
        }
        return $games;
    }
    private function findGamesForIds($gameIds)
    {
        if (!count($gameIds)) {
            return [];
        }
        
        $conn = $this->gameConn;

        $qb = $conn->createQueryBuilder();

        $qb->select('*');

        $qb->from('projectGames','game');
        
        $qb->where('game.id IN (?)');

        $qb->addOrderBy('game.gameNumber');

        $stmt = $conn->executeQuery($qb->getSQL(),[$gameIds],[Connection::PARAM_STR_ARRAY]);
        $games = [];
        while($game = $stmt->fetch()) {
            $game['teams'] = [];
            $game['gameNumber'] = (integer)$game['gameNumber'];
            $games[$game['id']] = $game;
        }
        return $games;
    }
    private function findGameIds(array $criteria)
    {
        $gameIds = [];

        $conn = $this->gameConn;

        $qb = $conn->createQueryBuilder();

        $qb->select('DISTINCT game.id');

        $qb->from('projectGames','game');
        
        $qb->leftJoin('game','projectGameTeams','team','team.gameId = game.id');
        
        $qb->orderBy ('game.id');

        $whereMeta = [
            'dates'       => 'DATE(game.start)',
            'poolTeamIds' => 'team.poolTeamId',
        ];
        $info = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $conn->executeQuery($qb->getSQL(),$info[0],$info[1]);
        while($gameId = $stmt->fetch()) {
            $gameIds[] = $gameId['id'];
        }
        return $gameIds;
    }
    
    // It is possible that this might be moved into a shared pool team finder
    public function findPoolTeamIds(array $criteria)
    {
        $qb = $this->poolConn->createQueryBuilder();

        $qb->select('distinct id')->from('projectPoolTeams');

        $whereMeta = [
            'projectKeys'    => 'projectKey',
            'programs'       => 'program',
            'genders'        => 'gender',
            'ages'           => 'age',
            'divisions'      => 'division',
            'poolTypes'      => 'poolType',
            'projectTeamIds' => 'projectTeamId',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);

        if (!count($values)) {
            //return [];
        }
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);

        $poolTeamIds = [];
        while($row = $stmt->fetch()) {
            $poolTeamIds[] = $row['id'];
        }
        return $poolTeamIds;
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

                if ($game1->projectKey > $game2->projectKey) return  1;
                if ($game1->projectKey < $game2->projectKey) return -1;

                if ($game1->gameNumber > $game2->gameNumber) return  1;
                if ($game1->gameNumber < $game2->gameNumber) return -1;

                return 0;
            });
            return $games;
        }
        return $games;
    }
    /** ===========================================================================
     * This might be moved to it's own finder?
     *
     * @param  array $criteria
     * @param  bool  $objects
     * @return SchedulePoolTeam[]|array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findPoolTeams(array $criteria, $objects = true)
    {
        $qb = $this->poolConn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*')->from('projectPoolTeams')->orderBy('id');

        $whereMeta = [
            'poolTeamIds' => 'id',
            'regTeamIds'  => 'projectTeamId',
            'projectKeys' => 'projectKey',

            'poolKeys'     => 'poolKey',
            'poolTypes'    => 'poolType',
            'poolTeamKeys' => 'poolTeamKey',

            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $poolTeams  = [];
        $regTeamIds = [];
        while($poolTeam = $stmt->fetch()) {

            $poolTeams[$poolTeam['id']] = $poolTeam;

            $regTeamId = $poolTeam['projectTeamId'];
            if ($regTeamId) {
                $regTeamIds[$regTeamId][] = $poolTeam;
            }
        }
        // Link Reg Teams
        $criteria = ['regTeamIds' => array_keys($regTeamIds)];
        $regTeams = $this->findRegTeams($criteria,false);
        foreach($regTeamIds as $regTeamId => $poolTeamLinks) {
            foreach($poolTeamLinks as $poolTeam) {
                $regTeam = $regTeams[$regTeamId];
                unset($regTeam['id']);
                unset($regTeam['projectKey']);
                unset($regTeam['status']);
                unset($regTeam['program']);
                unset($regTeam['gender']);
                unset($regTeam['age']);
                unset($regTeam['division']);
                $poolTeam = array_replace($poolTeam,$regTeam);
                $poolTeams[$poolTeam['id']] = $poolTeam;
            }
        }
        // Convert to objects
        if (!$objects) {
            return $poolTeams;
        }
        $poolTeamObjects = [];
        foreach($poolTeams as $poolTeam) {
            $poolTeamObjects[] = SchedulePoolTeam::createFromArray($poolTeam);
        }
        return $poolTeamObjects;
    }
    /** ===========================================================================
     * This might be moved to it's own finder?
     *
     * @param  array $criteria
     * @param  bool  $objects
     * @return ScheduleRegTeam[]|array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegTeams(array $criteria, $objects = true)
    {
        $qb = $this->teamConn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*')->from('projectTeams')->orderBy('id');

        $whereMeta = [
            'regTeamIds'  => 'id',
            'projectKeys' => 'projectKey',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $teams = [];
        while($team = $stmt->fetch()) {
            $teams[$team['id']] = $objects ? ScheduleRegTeam::createFromArray($team) : $team;
        }
        return $teams;
    }
}