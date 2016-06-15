<?php
namespace AppBundle\Action\Game;

use Doctrine\DBAL\Connection;

class GameFinder
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
     * Shortcut for finding one game
     * @param  string  $projectId
     * @param  integer $gameNumber
     * @return Game
     */
    public function findGame($projectId,$gameNumber)
    {
        $criteria = [
            'projectIds'    => [$projectId],
            'gameNumbers'   => [$gameNumber],
            'wantTeams'     => true,
            'wantOfficials' => true,
        ];
        $games = $this->findGames($criteria);
        
        return count($games) === 1 ? $games[0] : null;
    }
    /** =======================================================================
     * @param  array $criteria
     * @return Game[]
     */
    public function findGames(array $criteria)
    {
        $conn = $this->gameConn;

        // Now find unique game ids
        $gameIds = $this->findGameIds($conn,$criteria);

        // Load the games
        $games = $this->findGamesForIds($conn,$gameIds);

        // Load the teams
        $wantTeams = isset($criteria['wantTeams']) ? $criteria['wantTeams'] : true;
        if ($wantTeams) {
            $games = $this->joinTeamsToGames($conn, $games);
        }
        
        // Load the officials
        $wantOfficials = isset($criteria['wantOfficials']) ? $criteria['wantOfficials'] : false;
        if ($wantOfficials) {
            $games = $this->joinOfficialsToGames($conn,$games);
        }
        
        // Array based sort
        if (isset($criteria['sortBy'])) {
            //$games = $this->sortGames($games,$criteria['sortBy']);
        }

        // Convert to objects
        $gameObjects = [];
        foreach($games as $game) {
            $gameObjects[] = Game::createFromArray($game);
        }
        // Done
        return $gameObjects;
    }

    /** =======================================================================
     * @param  array $criteria
     * @return PoolTeam[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findPoolTeams(array $criteria)
    {
        $qb = $this->gameConn->createQueryBuilder();

        $qb->select('*')->from('poolTeams')->orderBy('poolTeamId');

        $whereMeta = [
            'poolTeamIds'  => 'poolTeamId',
            'poolTypeKeys' => 'poolTypeKey',
            'regTeamIds'   => 'regTeamId',
            'poolTeamKeys' => 'poolTeamKey',
            'projectIds'   => 'projectId',
            'programs'     => 'program',
            'genders'      => 'gender',
            'ages'         => 'age',
            'divisions'    => 'division',
        ];
        list($values,$types) = $this->addWhere($qb,$whereMeta,$criteria);
        $stmt = $qb->getConnection()->executeQuery($qb->getSQL(),$values,$types);
        $poolTeams = [];
        while($poolTeamRow = $stmt->fetch()) {
            $poolTeams[] = PoolTeam::createFromArray($poolTeamRow);
        }
        return $poolTeams;
    }
    /** =======================================================================
     * @param  array $criteria
     * @return RegTeam[]
     * @throws \Doctrine\DBAL\DBALException
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
        // Join the pool keys
        $sql = 'SELECT regTeamId,poolKey FROM poolTeams WHERE regTeamId IN (?) ORDER BY regTeamId,poolKey';
        $stmt = $this->gameConn->executeQuery($sql,[array_keys($regTeams)],[Connection::PARAM_STR_ARRAY]);
        while($row = $stmt->fetch()) {
            $regTeams[$row['regTeamId']]->addPoolKey($row['poolKey']);
        }
        return array_values($regTeams);
    }
}
