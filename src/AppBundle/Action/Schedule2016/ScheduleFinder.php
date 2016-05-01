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
    public function findGames(array $criteria, $objects = false)    
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

        // Convert to objects
        if (!$objects) {
            return array_values($games);
        }
        $gameObjects = [];
        foreach($games as $game) {
            $gameObjects[] = ScheduleGame::fromArray($game);
        }
        // Done
        return $gameObjects;
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
    public function findPoolTeams(array $criteria)
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
    // It is possible that this might be moved into a shared project team finder
    public function findProjectTeams(array $criteria, $objects = true)
    {
        $conn = $this->teamConn;
        
        $qb = $conn->createQueryBuilder();

        // Just grab everything for now
        $qb->select('*')->from('projectTeams')->orderBy('id');

        $whereMeta = [
            'projectKeys' => 'projectKey',
            'programs'    => 'program',
            'genders'     => 'gender',
            'ages'        => 'age',
            'divisions'   => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $conn->executeQuery($qb->getSQL(),$values,$types);
        $teams = [];
        while($team = $stmt->fetch()) {
            $teams[$team['id']] = $objects ? ScheduleTeam::createFromArray($team) : $team;
        }
        return $teams;
    }
}