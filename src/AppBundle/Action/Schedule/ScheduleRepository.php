<?php
namespace AppBundle\Action\Schedule;

use Doctrine\DBAL\Connection;

/** ================================================
 *  This is basically a view model
 */
class ScheduleRepository
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    protected function extractFromRow(array $row, $prefix)
    {
        $len = strlen($prefix);
        $item = [];
        foreach($row as $key => $value) {
            if (substr($key,0,$len) === $prefix) {
                $item[substr($key,$len)] = $value;
            }
        }
        return $item;
    }
    public function findProjectTeams($projectKey)
    {
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_team.id         AS  id',
            'project_team.keyx       AS `key`',
            'project_team.name       AS  name',
            'project_team.projectKey AS  project_key',
            'project_team.levelKey   AS  level_key',
        ]);
        $qb->from('teams','project_team');

        $qb->andWhere('project_team.projectKey = :projectKey');
        $qb->andWhere('project_team.levelKey LIKE :levelKey');
        $qb->andWhere("project_team.status = 'Active'");

        $qb->setParameter(':projectKey',$projectKey);
        $qb->setParameter(':levelKey','%_Core');

        $qb->addOrderBy('project_team.levelKey','DESC');

        $stmt = $qb->execute();
        $projectTeams = [];
        while($projectTeam = $stmt->fetch()) {

            $levelParts = explode('_',$projectTeam['level_key']);
            $projectTeam['div'] = $levelParts[1];

            $projectTeams[] = $projectTeam;
        }
        return $projectTeams;
    }
    public function findProjectGamesForProjectTeamKeys(array $projectTeamKeys)
    {
        if (count($projectTeamKeys) < 1) return [];

        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect('distinct project_game.id AS id');

        $qb->from('games','project_game');

        $qb->leftJoin(
            'project_game',
            'game_teams',
            'project_game_team',
            'project_game_team.gameId = project_game.id'
        );
        $qb->andWhere('project_game_team.teamKey IN (?)');

        $stmt = $this->conn->executeQuery($qb->getSQL(),[$projectTeamKeys],[Connection::PARAM_STR_ARRAY]);

        $projectGameIds = [];
        while($row = $stmt->fetch()) {
            $projectGameIds[] = $row['id'];
        }
        return $this->findProjectGamesForProjectGamesIds($projectGameIds);
    }
    protected function genLevelKeys(array $criteria)
    {
        $feds = ['AYSO'];
        $programs = isset($criteria['programs']) ? $criteria['programs'] : [];
        $genders  = isset($criteria['genders' ]) ? $criteria['genders']  : [];
        $ages     = isset($criteria['ages'    ]) ? $criteria['ages']     : [];

        $levelKeys = [];
        foreach($feds as $fed) {
            foreach ($programs as $program) {
                foreach ($genders as $gender) {
                    foreach ($ages as $age) {
                        // TODO VIP
                        $levelKeys[] = sprintf('%s_%s%s_%s', $fed, $age, $gender, $program);
                    }
                }
            }
        }
        return $levelKeys;
    }
    public function findProjectGames(array $criteria)
    {
        // Never grab everything by accident
        if (count($criteria) < 1) return [];

        $levelKeys = $this->genLevelKeys($criteria);

        $paramValues = [];
        $paramTypes  = [];

        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect('distinct project_game.id AS id');

        $qb->from('games','project_game');

        $qb->leftJoin(
            'project_game',
            'game_teams',
            'project_game_team',
            'project_game_team.gameId = project_game.id'
        );

        if (isset($criteria['projects']) && count($criteria['projects'])) {
            $paramValues[] = $criteria['projects'];
            $paramTypes [] = Connection::PARAM_STR_ARRAY;
            $qb->andWhere('project_game.projectKey IN (?)');
        }
        if (isset($criteria['dates']) && count($criteria['dates'])) {
            $paramValues[] = $criteria['dates'];
            $paramTypes [] = Connection::PARAM_STR_ARRAY;
            $qb->andWhere('DATE(project_game.dtBeg) IN (?)');
        }
        if (count($levelKeys)) {
            $paramValues[] = $levelKeys;
            $paramTypes [] = Connection::PARAM_STR_ARRAY;
            $qb->andWhere('project_game.levelKey IN (?)');
        }
        if (isset($criteria['group_types']) && count($criteria['group_types'])) {
            $paramValues[] = $criteria['group_types'];
            $paramTypes [] = Connection::PARAM_STR_ARRAY;
            $qb->andWhere('project_game.groupType IN (?)');
        }
        if (isset($criteria['group_names']) && count($criteria['group_names'])) {
            $paramValues[] = $criteria['group_names'];
            $paramTypes [] = Connection::PARAM_STR_ARRAY;
            $qb->andWhere('project_game.groupName IN (?)');
        }

        if (count($paramValues) < 1) return [];

        $stmt = $this->conn->executeQuery($qb->getSQL(),$paramValues,$paramTypes);

        $projectGameIds = [];
        while($row = $stmt->fetch()) {
            $projectGameIds[] = $row['id'];
        }
        return $this->findProjectGamesForProjectGamesIds($projectGameIds);
    }
    protected function findProjectGamesForProjectGamesIds(array $projectGameIds)
    {
        if (count($projectGameIds) < 1) return [];

        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_game.id         AS id',
            'project_game.num        AS number',
            'project_game.dtBeg      AS start',
            'project_game.fieldName  AS field_name',
            'project_game.groupType  AS group_type',
            'project_game.groupName  AS group_name',
            'project_game.levelKey   AS level_key',
            'project_game.projectKey AS project_key',
            'project_game.status     AS status',
        ]);
        $qb->from('games','project_game');

        $qb->addOrderBy('project_game.dtBeg',   'ASC');
        $qb->addOrderBy('project_game.levelKey','ASC');
        $qb->addOrderBy('project_game.num',     'ASC');

        $qb->andWhere('project_game.id IN (?)');

        $stmt = $this->conn->executeQuery($qb->getSQL(),[$projectGameIds],[Connection::PARAM_INT_ARRAY]);

        $projectGames = [];
        while($projectGame = $stmt->fetch()) {

            $start = \DateTime::createFromFormat('Y-m-d H:i:s',$projectGame['start']);

            // Display only
            $projectGame['dow']  = $start->format('D');
            $projectGame['time'] = $start->format('g:i A');

            // Is this for display only or is used by the calculator?
            $levelParts = explode('_',$projectGame['level_key']);
            $projectGame['group_key'] = sprintf('%s %s %s %s',
                $levelParts[1],
                $levelParts[2],
                $projectGame['group_type'],
                $projectGame['group_name']
            );
            $projectGame['project_game_teams'] = [];

            $projectGames[$projectGame['id']] = $projectGame;
        }
        // Add teams
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_game_team.id         AS  id',
            'project_game_team.slot       AS  slot',
            'project_game_team.teamKey    AS `key`',
            'project_game_team.teamName   AS  name',
            'project_game_team.teamPoints AS  points',
            'project_game_team.groupSlot  AS  group_slot',
            'project_game_team.gameId     AS  project_game_id',
            'project_game_team.report     AS  report',
        ]);
        $qb->from('game_teams','project_game_team');

        $qb->addOrderBy('project_game_id','ASC');
        $qb->addOrderBy('slot',           'ASC');

        $qb->andWhere('project_game_team.gameId IN (?)');

        $stmt = $this->conn->executeQuery($qb->getSQL(),[$projectGameIds],[Connection::PARAM_INT_ARRAY]);

        while($projectGameTeam = $stmt->fetch()) {

            $projectGameTeam['report'] = unserialize($projectGameTeam['report']);

            $projectGames[$projectGameTeam['project_game_id']]['teams'][$projectGameTeam['slot']] = $projectGameTeam;
        }
        return $projectGames;
    }
}