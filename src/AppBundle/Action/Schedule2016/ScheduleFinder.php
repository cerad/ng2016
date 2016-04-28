<?php
namespace AppBundle\Action\Schedule2016;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class ScheduleFinder
{
    /** @var  Connection */
    private $gameConn;
    
    /** @var  Connection */
    private $poolConn;

    public function __construct(Connection $gameConn, Connection $poolConn)
    {
        $this->gameConn = $gameConn;
        $this->poolConn = $poolConn;
    }
    public function findGames(array $criteria)
    {
        // First we need pool teams
        $poolTeams = $this->findPoolTeams($criteria);

        $criteria['poolTeamIds'] = array_keys($poolTeams);

        // Now find unique game ids
        $gameIds = $this->findGameIds($criteria);

        // Load the games
        $games = $this->findGamesForIds($gameIds);

        // Load the teams
        $games = $this->joinTeamsToGames($games,$poolTeams);

        // Done
        return array_values($games);
    }
    private function joinTeamsToGames(array $games, array $poolTeams)
    {
        if (!count($games)) {
            return [];
        }

        $conn = $this->gameConn;

        $qb = $conn->createQueryBuilder();

        $qb->select('*');

        $qb->from('projectGameTeams','team');

        $qb->where('team.gameId IN (?)');

        $qb->addOrderBy('team.slot');

        $stmt = $conn->executeQuery($qb->getSQL(),[array_keys($games)],[Connection::PARAM_STR_ARRAY]);

        while($team = $stmt->fetch()) {

            // Merge in pool team info
            $poolTeam = $poolTeams[$team['poolTeamId']];
            unset($poolTeam['id']);
            unset($poolTeam['projectKey']);
            $team = array_merge($team,$poolTeam);

            // And stash
            $gameId = $team['gameId'];
            $games[$gameId]['teams'][$team['slot']] = $team;
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

        $qb->addOrderBy('game.start');
        $qb->addOrderBy('game.fieldName');

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
    private function findPoolTeams(array $criteria)
    {
        $poolTeams = [];

        $conn = $this->poolConn;

        $poolTeamsTableName = $conn->getDatabase() . '.projectPoolTeams';

        $qb = $conn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*');

        $qb->from($poolTeamsTableName);

        $qb->orderBy('poolTeamKey');

        $whereMeta = [
            'projectKeys' => 'projectKey',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
            'poolTypes'   => 'poolType',
        ];
        $info = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $conn->executeQuery($qb->getSQL(),$info[0],$info[1]);
        while($poolTeam = $stmt->fetch()) {
            $poolTeams[$poolTeam['id']] = $poolTeam;
        }
        return $poolTeams;
    }
    private function addWhere(QueryBuilder $qb, array $metas, array $criteria)
    {
        $values = [];
        $types  = [];
        
        foreach($metas as $key => $col) {
            if (isset($criteria[$key]) && count($criteria[$key])) {
                $qb->andWhere($col . ' IN (?)');
                $values[] = $criteria[$key];
                $types[]  = Connection::PARAM_STR_ARRAY;
            }
        }
        return [$values,$types];
    }
}