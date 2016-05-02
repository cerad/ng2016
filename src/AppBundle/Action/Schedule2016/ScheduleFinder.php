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
            return array_values($games);
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
$sql = <<<EOD
SELECT * 
FROM projectGameTeams AS team
WHERE team.gameId IN (?)
ORDER BY team.gameNumber,team.slot
EOD;
        $stmt = $this->gameConn->executeQuery($sql,[array_keys($games)],[Connection::PARAM_STR_ARRAY]);
        $poolTeamIds = [];
        while($gameTeam = $stmt->fetch()) {

            $gameTeam['name'] = null; // Should come from regTeam

            $gameId = $gameTeam['gameId'];
            $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;

            $poolTeamId = $gameTeam['poolTeamId'];
            $poolTeamIds[$poolTeamId][] = $gameTeam;
        }
        // Merge Pool Teams (move to it's own function later?
        $sql = <<<EOD
SELECT * 
FROM projectPoolTeams AS poolTeam
WHERE poolTeam.id IN (?)
EOD;
        $stmt = $this->poolConn->executeQuery($sql,[array_keys($poolTeamIds)],[Connection::PARAM_STR_ARRAY]);
        $projectTeamIds = [];
        while($poolTeam = $stmt->fetch()) {

            $poolTeamId = $poolTeam['id'];
            foreach($poolTeamIds[$poolTeamId] as $gameTeam)
            {
                unset($poolTeam['id']);
                $gameTeam = array_replace($gameTeam,$poolTeam);

                $gameId = $gameTeam['gameId'];
                $games[$gameId]['teams'][$gameTeam['slot']] = $gameTeam;

                // Need ids for later query
                $projectTeamId = $poolTeam['projectTeamId'];
                if ($projectTeamId) {
                    $projectTeamIds[$projectTeamId][] = $gameTeam;
                }
            }
        }
        // Merge Project Teams (move to it's own function later?
        $sql = <<<EOD
SELECT id,name,teamKey,teamNumber,coach,points,orgKey
FROM projectTeams AS regTeam
WHERE regTeam.id IN (?)
EOD;
        $stmt = $this->teamConn->executeQuery($sql,[array_keys($projectTeamIds)],[Connection::PARAM_STR_ARRAY]);
        while($regTeam = $stmt->fetch()) {

            $regTeamId = $regTeam['id'];
            foreach($projectTeamIds[$regTeamId] as $gameTeam)
            {
                unset($regTeam['id']);
                unset($regTeam['status']);
                $gameTeam = array_merge($gameTeam,$regTeam);

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

    /** ===========================================================================
     * @param  array $criteria
     * @param  bool $objects
     * @return ScheduleTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRegTeams(array $criteria)
    {
        return $this->findProjectTeams($criteria,true);
    }
    /** ===========================================================================
     * @param  array $criteria
     * @param  bool $objects
     * @return ScheduleTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findProjectTeams(array $criteria, $objects = true)
    {
        $conn = $this->teamConn;
        
        $qb = $conn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*')->from('projectTeams')->orderBy('id');

        $whereMeta = [
            'projectTeamIds' => 'id',
            'projectKeys'    => 'projectKey',
            'programs'       => 'program',
            'genders'        => 'gender',
            'ages'           => 'age',
            'divisions'      => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $conn->executeQuery($qb->getSQL(),$values,$types);
        $teams = [];
        while($team = $stmt->fetch()) {
            $teams[$team['id']] = $objects ? ScheduleTeam::createFromArray($team) : $team;
        }
        return $teams;
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
        return $games;
    }
}