<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Common\DatabaseTrait;

use AppBundle\Common\DirectoryTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

class MigrateGames2014Command extends Command
{
    use DatabaseTrait;
    use DirectoryTrait;
    use MigrateGames2014Trait;

    private $ng2014GamesConn;
    private $ng2016GamesConn;

    private $gameConn;
    private $poolConn;
    private $projectTeamConn;

    public function __construct(Connection $ng2014GamesConn, Connection $ng2016GamesConn)
    {
        parent::__construct();

        $this->ng2014GamesConn = $ng2014GamesConn;
        $this->ng2016GamesConn = $ng2016GamesConn;

        $this->gameConn        = $ng2016GamesConn;
        $this->poolConn        = $ng2016GamesConn;
        $this->projectTeamConn = $ng2016GamesConn;
    }
    protected function configure()
    {
        $this
            ->setName('migrate:games:ng2014')
            ->setDescription('Migrate Games NG2014');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        $this->resetDatabase($this->ng2016GamesConn,$schemaFile);

        echo sprintf("Migrate Games NG2014 ...\n");
        if (1) {
            $this->migrateProjectTeams(true);

            $this->migratePoolTeams(true);

            $this->migrateGames(true);

            $this->migrateGameTeams(true);
        }
        $this->migrateGameOfficials(true);

        echo sprintf("Migrate Games NG2014 Completed.\n");
    }
    /* ======================================================================
     * Migrate Project Teams
    */
    public function migrateProjectTeams($commit)
    {
        if (!$commit) return;

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

            list($program,$gender,$age,$division) = $this->parseLevelKey($levelKey);

            $parts = explode('-',$row['orgKey']);
            $regionNumber = (integer)$parts[2];
            $orgKey = sprintf('AYSOR:%04d',$regionNumber);

            $teamKey = sprintf('%s-%s-%02d',$division,$program,$teamNumber);

            $projectTeamId = $projectKey . ':' . $teamKey;

            $item = [
                'id'         => $projectTeamId,
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
            $this->projectTeamConn->delete('projectTeams',['id' => $projectTeamId]);
            $this->projectTeamConn->insert('projectTeams',$item);

            $projectTeams[] = $item;

            if (($projectTeamCount % 100) === 0) {
                echo sprintf("\rMigrating Project Teams %5d",$projectTeamCount);
            }
        }

        echo sprintf("\rMigrated Project Teams %5d      \n",$projectTeamCount);

        file_put_contents('var/project_teams.yml',Yaml::dump($projectTeams,9));
    }
    /* ==============================================================
     * Create all the pool teams
     * SELECT poolKey,poolTeamKey,division,projectTeamId FROM projectPoolTeams WHERE program = 'Core' AND division = 'U14B';
     */
    private function migratePoolTeams($commit)
    {
        if (!$commit) return;

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

            list($program,$gender,$age,$division) = $this->parseLevelKey($row['levelKey']);

            list(
                $poolType,$poolKey,$poolTeamKey,
                $poolTypeView,$poolView,$poolTeamView,$poolTeamSlotView
                ) = $this->generatePoolInfo(
                    $program,$gender,$age,$division,
                    $row['groupType'],$row['groupName'],$row['groupSlot']
            );

            $projectKey = $row['projectKey'];
            $poolTeamId = $projectKey . ':' . $poolTeamKey;

            $projectTeamId = $this->generateProjectTeamId($projectKey,$row['teamKey'],$program,$division);

            $item = [
                'id' => $poolTeamId,

                'projectKey'   => $projectKey,

                'poolType'     => $poolType,
                'poolKey'      => $poolKey,
                'poolTeamKey'  => $poolTeamKey,

                'poolView'         => $poolView,
                'poolTypeView'     => $poolTypeView,
                'poolTeamView'     => $poolTeamView,
                'poolTeamSlotView' => $poolTeamSlotView,

                'program'  => $program,
                'gender'   => $gender,
                'age'      => $age,
                'division' => $division,

                'projectTeamId' => $projectTeamId,
            ];
            $poolTeams[] = $item;

            // Note: AYSONationalGames2014:U14G-Extra-PP-A5 dup
            $this->poolConn->delete('projectPoolTeams',['id' => $poolTeamId]);
            $this->poolConn->insert('projectPoolTeams',$item);

            if (($poolTeamCount % 100) === 0) {
                echo sprintf("\rMigrating Pool Teams %5d",$poolTeamCount);
            }
        }
        echo sprintf("\rMigrated Pool Teams %5d      \n",$poolTeamCount);

        file_put_contents('var/pool_teams.yml',Yaml::dump($poolTeams,9));
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
  projectKey AS projectKey,
  num        AS gameNumber,
  venueName  AS venueName,
  fieldName  AS fieldName,
  dtBeg      AS start,
  dtEnd      AS finish,
  status     AS status,
  report     AS report
FROM games WHERE projectKey = 'AYSONationalGames2014' ORDER BY gameNumber
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $games = [];
        $gameCount = 0;
        while ($row = $stmt->fetch()) {

            $gameCount++;

            $row['id'] = sprintf('%s:%s',$row['projectKey'],$row['gameNumber']);

            $report = isset($row['report']) ? unserialize($row['report']) : [];
            $report = is_array($report) ? $report : [];
            $report = array_merge(['status' => null, 'text' => null],$report);

            unset($row['report']);
            $row['reportText']  = $report['text'];
            $row['reportState'] = $report['status'] ? : 'Unknown';

            $this->gameConn->delete('projectGames',['id' => $row['id']]);
            $this->gameConn->insert('projectGames',$row);

            $games[] = $row;

            if (($gameCount % 100) === 0) {
                echo sprintf("\rMigrating Games %5d",$gameCount);
            }
        }
        echo sprintf("\rMigrated Games %5d      \n",$gameCount);

        file_put_contents('var/games.yml',Yaml::dump($games,9));
    }
    /* ===================================================================
     * Load up the games with teams
     */
    private function migrateGameTeams($commit)
    {
        if (!$commit) return;

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
        $gameTeams = [];
        while($row = $stmt->fetch()) {

            // Need a pool team key
            list($program,$gender,$age,$division) = $this->parseLevelKey($row['levelKey']);

            $poolInfo = $this->generatePoolInfo(
                $program,$gender,$age,$division,
                $row['groupType'],$row['groupName'],$row['groupSlot']
            );
            $poolTeamKey = $poolInfo[2];

            $projectKey = $row['projectKey'];
            $gameNumber = (integer)$row['gameNumber'];
            $slot       = (integer)$row['slot'];

            $poolTeamId = $projectKey . ':' . $poolTeamKey;
            $gameId     = $projectKey . ':' . $gameNumber;
            $gameTeamId = $gameId .     ':' . $slot;

            $gameTeam = [
                'id'         => $gameTeamId,
                'projectKey' => $projectKey,
                'gameNumber' => $gameNumber,
                'slot'       => $slot,
                'name'       => $row['teamName'],

                'gameId'     => $gameId,
                'poolTeamId' => $poolTeamId,
            ];
            $report = isset($row['report']) ? unserialize($row['report']) : [];
            $report = is_array($report) ? $report : [];

            $metas = [
                'pointsScored'   => 'goalsScored',
                'pointsAllowed'  => 'goalsAllowed',
                'pointsEarned'   => 'pointsEarned',
                'pointsDeducted' => 'pointsMinus',  // From misconduct
                'sportsmanship'  => 'sportsmanship',
                'injuries'       => 'injuries',
            ];
            foreach($metas as $key2016 => $key2014) {
                $gameTeam[$key2016] = isset($report[$key2014]) ? (integer)$report[$key2014] : null;
            }
            $pointsScored  = $gameTeam['pointsScored'];
            $pointsAllowed = $gameTeam['pointsAllowed'];
            if ($pointsScored !== null && $pointsAllowed !== null) {
                if ($pointsScored > $pointsAllowed) {
                    $gameTeam['results']       = 1;
                    $gameTeam['resultsDetail'] = 'Won';
                }
                if ($pointsScored < $pointsAllowed) {
                    $gameTeam['results']       = 2;
                    $gameTeam['resultsDetail'] = 'Lost';
                }
                if ($pointsScored === $pointsAllowed) {
                    $gameTeam['results']       = 3;
                    $gameTeam['resultsDetail'] = 'Tied';
                }
            }
            // Misconduct
            $metas = [
                'playerWarnings', 'playerEjections',
                'coachWarnings',  'coachEjections',
                'benchWarnings',  'benchEjections',
                'specWarnings',   'specEjections',
            ];
            $misconduct = [];
            foreach($metas as $key) {
                if (isset($report[$key]))
                {
                    $misconduct[$key] = (integer)$report[$key];
                }
            }
            $gameTeam['misconduct'] = $misconduct;

            $gameTeams[] = $gameTeam; // For yaml

            $gameTeam['misconduct'] = count($misconduct) ? serialize($misconduct) : null;

            $this->gameConn->delete('projectGameTeams',['id' => $gameTeam['id']]);
            $this->gameConn->insert('projectGameTeams',$gameTeam);

            if ((count($gameTeams) % 100) === 0) {
                echo sprintf("\rMigrating Game Teams %5d",count($gameTeams));
            }
        }
        echo sprintf("\rMigrated Game Teams %5d      \n",count($gameTeams));

        file_put_contents('var/game_teams.yml',Yaml::dump($gameTeams,9));
    }
    /* ===================================================================
     * Load Game Officials
     */
    private function migrateGameOfficials($commit)
    {
        if (!$commit) return;

        // The teams
        $sql = <<<EOD
SELECT 
  official.slot           AS slot,
  official.assignRole     AS assignRole,
  official.assignState    AS assignState,
  official.personNameFull AS name,
  official.personGuid     AS personKey,
  game.projectKey         AS projectKey,
  game.num                AS gameNumber
FROM game_officials AS official
LEFT JOIN games AS game ON game.id = official.gameId
ORDER BY gameNumber,official.slot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $gameOfficials = [];
        while($row = $stmt->fetch()) {

            $projectKey = $row['projectKey'];
            $gameNumber = (integer)$row['gameNumber'];
            $slot       = (integer)$row['slot'];

            $gameId          = $projectKey . ':' . $gameNumber;
            $gameOfficialId  = $gameId .     ':' . $slot;
            $projectPersonId = $projectKey . ':' . $row['personKey'];

            $assignRole = $row['assignRole'];
            $assignRole = $assignRole === 'ROLE_USER' ? 'ROLE_REFEREE' : $assignRole;

            $gameOfficial = [
                'id'          => $gameOfficialId,
                'projectKey'  => $projectKey,
                'gameNumber'  => $gameNumber,
                'slot'        => $slot,
                'name'        => $row['name'],
                'assignRole'  => $assignRole,
                'assignState' => $row['assignState'],

                'gameId'          => $gameId,
                'projectPersonId' => $projectPersonId,
            ];

            $gameOfficials[] = $gameOfficial;

            $this->gameConn->delete('projectGameOfficials',['id' => $gameOfficialId]);
            $this->gameConn->insert('projectGameOfficials',$gameOfficial);

            if ((count($gameOfficials) % 100) === 0) {
                echo sprintf("\rMigrating Game Officials %5d",count($gameOfficials));
            }
        }
        echo sprintf("\rMigrated Game Officials %5d      \n",count($gameOfficials));

        file_put_contents('var/game_officials.yml',Yaml::dump($gameOfficials,9));
    }
}
