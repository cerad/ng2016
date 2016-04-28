<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameRepository;
use AppBundle\Action\Game\GameTeam;
use AppBundle\Action\Game\PoolTeam;
use AppBundle\Action\Game\PoolTeamRepository;

use AppBundle\Common\DatabaseInitTrait;

use Symfony\Component\Console\Command\Command;
//  Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//  Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

//  Symfony\Component\Yaml\Yaml;

class MigrateGames2014Command extends Command
{
    use DatabaseInitTrait;

    //private $maxCnt = 10000; //10000;

    private $ng2014GamesConn;
    private $ng2016GamesConn;

    private $poolConn;
    private $projectTeamConn;

    private $gameRepository;
    private $poolTeamRepository;

    public function __construct(Connection $ng2014GamesConn, Connection $ng2016GamesConn)
    {
        parent::__construct();

        $this->ng2014GamesConn = $ng2014GamesConn;
        $this->ng2016GamesConn = $ng2016GamesConn;

        $this->poolConn        = $ng2016GamesConn;
        $this->projectTeamConn = $ng2016GamesConn;

        $this->poolTeamRepository = new PoolTeamRepository($ng2016GamesConn);
        $this->gameRepository     = new GameRepository($ng2016GamesConn,$this->poolTeamRepository);
    }
    protected function configure()
    {
        $this
            ->setName('migrate:games:ng2014')
            ->setDescription('Migrate Games NG2014');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->resetDatabase($this->ng2016GamesConn, __DIR__ . '/../schema.sql');

        echo sprintf("Migrate Games NG2014 ...\n");

        $this->migrateProjectTeams(true);

        $this->migratePoolTeams(true);

        $this->migrateGames(false);

        echo sprintf("Migrate Games NG2014 Completed.\n");
    }
    /* ==============================================================
     * Create all the pool teams
     */
    private function migratePoolTeams($commit)
    {
        if (!$commit) return;

        $conn = $this->poolConn;

        $sql = <<<EOD
SELECT DISTINCT 
  game.projectKey, 
  game.levelKey, 
  game.groupType, 
  game.groupName, 
  team.groupSlot,
  team.teamKey    -- AYSONationalGames2014:AYSO_U10B_Core:01
FROM games AS game
LEFT JOIN game_teams AS team ON team.gameId = game.id
ORDER  BY game.levelKey, game.groupType, game.groupName, team.groupSlot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $poolTeams = [];
        $poolTeamCount = 0;
        while($row = $stmt->fetch()) {

            $poolTeamCount++;

            $levelKey = $row['levelKey'];  // AYSO_U10B_Core
            $poolType = $row['groupType']; // PP
            $poolName = $row['groupName']; // F
            $poolSlot = $row['groupSlot']; // F6

            $levelKeyParts = explode('_',$levelKey);

            $division = $levelKeyParts[1];
            $program  = $levelKeyParts[2];
            $gender   = substr($division,3,1);
            $age      = substr($division,0,3);

            switch($poolType) {
                case 'FM':  $poolType = 'TF'; break;
                case 'SOF': $poolType = 'ZZ'; break;
            }
            $poolView     = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolName);
            $poolTeamView = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolSlot);

            $poolKey     = sprintf('%s-%s-%s-%s',$division,$program,$poolType,$poolName);
            $poolTeamKey = sprintf('%s-%s-%s-%s',$division,$program,$poolType,$poolSlot);
            if ($poolType == 'ZZ') {
                $poolTeamKey = sprintf('%s-%s-%s-%s-%s',$division,$program,$poolType,$poolName,$poolSlot);
            }

            $projectKey = $row['projectKey'];
            $projectTeamId = null;
            if (isset($row['teamKey'])) {
                $parts = explode(':', $row['teamKey']);
                $teamNumber = (integer)$parts[2];
                $projectTeamId = sprintf('%s:%s-%s-%02d',$projectKey,$division,$program,$teamNumber);
            }
            $id = $projectKey . ':' . $poolTeamKey;

            $item = [
                'id' => $id,

                'projectKey'   => $projectKey,
                'poolType'     => $poolType,
                'poolKey'      => $poolKey,
                'poolTeamKey'  => $poolTeamKey,

                'poolView'         => $poolView,
                'poolTypeView'     => $poolType,
                'poolTeamView'     => $poolTeamView,
                'poolTeamSlotView' => $poolSlot,

                'program'  => $program,
                'gender'   => $gender,
                'age'      => $age,
                'division' => $division,

                'projectTeamId' => $projectTeamId,
            ];
            $poolTeams[] = $item;

            // Note: AYSONationalGames2014:U14G-Extra-PP-A5 dup
            $conn->delete('projectPoolTeams',['id' => $id]);
            $conn->insert('projectPoolTeams',$item);

            if (($poolTeamCount % 100) === 0) {
                echo sprintf("\rMigrating Pool Teams %5d",$poolTeamCount);
            }
        }
        echo sprintf("\rMigrated Pool Teams %5d      \n",$poolTeamCount);

        file_put_contents('var/pool_teams.yml',Yaml::dump($poolTeams,1));
    }
    /* ===================================================================
     * Load up the games with teams
     */
    private function migrateGames($commit)
    {
        if (!$commit) return;

        // The games
        $sql = <<<EOD
SELECT
  id         AS gameId,
  projectKey AS projectKey,
  num        AS gameNumber,
  venueName  AS venueName,
  fieldName  AS fieldName,
  dtBeg      AS start,
  dtEnd      AS finish,
  status     AS status,
  report     AS report
FROM games WHERE projectKey = 'AYSONationalGames2014'
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $games = [];
        while ($gameRow = $stmt->fetch()) {
            $gameRow['teams'] = [];
            $games[$gameRow['gameId']] = Game::fromArray($gameRow);;
        }
        // The teams
        $sql = <<<EOD
SELECT 
  team.*,
  game.projectKey AS projectKey,
  game.num        AS gameNumber,
  game.groupType  AS groupType,
  game.groupName  AS groupName
FROM game_teams   AS team
LEFT JOIN games   AS game ON game.id = team.gameId
ORDER BY gameNumber,team.slot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        while($teamRow = $stmt->fetch()) {

            $projectKey = $teamRow['projectKey'];

            // Need a pool team
            $levelKey = $teamRow['levelKey'];  // AYSO_U10B_Core
            $poolType = $teamRow['groupType']; // PP
            $poolName = $teamRow['groupName']; // F
            $poolSlot = $teamRow['groupSlot']; // F6

            $poolTeamKey = sprintf('%s_%s_%s',$levelKey,$poolType,$poolSlot);

            if ($poolType == 'SOF') {
                $poolTeamKey = sprintf('%s_%s_%s_%s',$levelKey,$poolType,$poolName,$poolSlot);
            }
            $poolTeam = new PoolTeam($projectKey,$poolTeamKey);

            $gameTeam = new GameTeam($projectKey,$teamRow['gameNumber'],$teamRow['slot']);
            $gameTeam->setPoolTeam($poolTeam);
            $gameTeam->name = $teamRow['teamName'];

            $games[$teamRow['gameId']]->addTeam($gameTeam);

            //var_dump($teamRow); die();
        }
        // And save them
        $gameCount = 0;
        foreach($games as $game) {

            $gameCount++;

            $this->gameRepository->save($game);

            if (($gameCount % 100) === 0) {
                echo sprintf("\rMigrating Games %5d",$gameCount);
            }
        }
        echo sprintf("\rMigrated Games %5d      \n",$gameCount);
    }
    /* ======================================================================
     * Migrate Project Teams
     */
    public function migrateProjectTeams($commit)
    {
        if (!$commit) return;

        $conn = $this->projectTeamConn;

        $sql = <<<EOD
SELECT 
  keyx       AS keyx,    -- AYSONationalGames2014:AYSO_U10B_Core:01
  orgKey     AS orgKey,  -- 10-W-68
  name       AS name,
  coach      AS coach,
  points     AS points,
  status     AS status
FROM teams ORDER BY keyx
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $projectTeams = [];
        $projectTeamCount = 0;
        while($row = $stmt->fetch()) {

            $projectTeamCount++;

            $parts     = explode(':',$row['keyx']);
            $projectKey = $parts[0];
            $levelKey   = $parts[1];
            $teamNumber = (integer)$parts[2];

            $parts    = explode('_',$levelKey);
            $program  = $parts[2];
            $division = $parts[1];
            $gender   = substr($division,3,1);
            $age      = substr($division,0,3);

            $parts = explode('-',$row['orgKey']);
            $regionNumber = (integer)$parts[2];
            $orgKey = sprintf('AYSOR:%04d',$regionNumber);

            $teamKey = sprintf('%s-%s-%02d',$division,$program,$teamNumber);

            $id = $projectKey . ':' . $teamKey;

            $item = [
                'id'         => $id,
                'projectKey' => $projectKey,
                'teamKey'    => $teamKey,
                'teamNumber' => $teamNumber,

                'name'   => $row['name'],
                'coach'  => $row['coach'],
                'points' => $row['points'],
                'status' => $row['status'],
                'orgKey' => $orgKey,

                'program'  => $program,
                'gender'   => $gender,
                'age'      => $age,
                'division' => $division,
            ];
            $conn->delete('projectTeams',['id' => $id]);
            $conn->insert('projectTeams',$item);

            $projectTeams[] = $item;

            if (($projectTeamCount % 100) === 0) {
                echo sprintf("\rMigrating Project Teams %5d",$projectTeamCount);
            }
        }
        echo sprintf("\rMigrated Project Teams %5d      \n",$projectTeamCount);
        file_put_contents('var/project_teams.yml',Yaml::dump($projectTeams,1));
    }
}