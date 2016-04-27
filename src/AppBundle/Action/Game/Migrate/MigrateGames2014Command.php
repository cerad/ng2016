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
//  Symfony\Component\Yaml\Yaml;

class MigrateGames2014Command extends Command
{
    use DatabaseInitTrait;

    //private $maxCnt = 10000; //10000;

    private $ng2014GamesConn;
    private $ng2016GamesConn;

    private $gameRepository;
    private $poolTeamRepository;

    public function __construct(Connection $ng2014GamesConn, Connection $ng2016GamesConn)
    {
        parent::__construct();

        $this->ng2014GamesConn = $ng2014GamesConn;
        $this->ng2016GamesConn = $ng2016GamesConn;

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
        //$this->resetDatabase($this->ng2016GamesConn, __DIR__ . '/../schema.sql');

        echo sprintf("Migrate Games NG2014 ...\n");

        $this->migratePoolTeams();

        $this->migrateGames();

        echo sprintf("Migrate Games NG2014 Completed.\n");
    }
    /** ==============================================================
     * Create all the pool teams
     */
    private function migratePoolTeams()
    {
        if (1) return;

        $sql = <<<EOD
SELECT DISTINCT 
  game.projectKey, game.levelKey, game.groupType, game.groupName, team.groupSlot
FROM games AS game
LEFT JOIN game_teams AS team ON team.gameId = game.id
ORDER  BY game.levelKey, game.groupType, game.groupName, team.groupSlot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $poolTeams = [];
        $rowCount = 0;
        while($row = $stmt->fetch()) {

            $rowCount++;

            $levelKey = $row['levelKey'];  // AYSO_U10B_Core
            $poolType = $row['groupType']; // PP
            $poolName = $row['groupName']; // F
            $poolSlot = $row['groupSlot']; // F6

            $poolKey     = sprintf('%s_%s_%s',$levelKey,$poolType,$poolName);
            $poolTeamKey = sprintf('%s_%s_%s',$levelKey,$poolType,$poolSlot);

            if ($poolType == 'SOF') {
                $poolKey     = sprintf('%s_%s_%s',   $levelKey,$poolType,$poolName);
                $poolTeamKey = sprintf('%s_%s_%s_%s',$levelKey,$poolType,$poolName,$poolSlot);
            }
            $levelKeyParts = explode('_',$levelKey);

            $division = $levelKeyParts[1];
            $program  = $levelKeyParts[2];
            $gender   = substr($division,3,1);
            $age      = substr($division,0,3);

            $poolView     = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolName);
            $poolTeamView = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolSlot);

            $item = [
                'projectKey'   => $row['projectKey'],
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
            ];
            $poolTeams[] = $item;
            $poolTeam = PoolTeam::fromArray($item);

            $this->poolTeamRepository->save($poolTeam);

            if (($rowCount % 100) === 0) {
                echo sprintf("\rMigrating Pool Teams %5d",$rowCount);
            }
        }
        echo sprintf("\rMigrated Pool Teams %5d      \n",$rowCount);

        //file_put_contents('var/pool_teams.yml',Yaml::dump($poolTeams,1));
    }
    /** ===================================================================
     * Load up the games with teams
     */
    private function migrateGames()
    {
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
}