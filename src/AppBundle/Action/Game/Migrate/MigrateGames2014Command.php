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

    private $ng2014ProjectId = 'AYSONationalGames2014';
    private $ng2014GamesConn;
    private $ng2016GamesConn;

    private $gameConn;
    private $poolConn;
    private $regTeamConn;
    private $projectTeamConn;

    public function __construct(Connection $ng2014GamesConn, Connection $ng2016GamesConn)
    {
        parent::__construct();

        $this->ng2014GamesConn = $ng2014GamesConn;
        $this->ng2016GamesConn = $ng2016GamesConn;

        $this->gameConn        = $ng2016GamesConn;
        $this->poolConn        = $ng2016GamesConn;
        $this->regTeamConn     = $ng2016GamesConn;
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
        $all = false;

        $schemaFile = $this->getRootDirectory() . '/schema2016games.sql';

        if ($all || false) {
            $this->resetDatabase($this->ng2016GamesConn, $schemaFile);
        }
        echo sprintf("Migrate Games NG2014 ...\n");

        $this->migrateRegTeams($all || false);

        $this->migratePoolTeams($all || false);

        $this->migrateGames($all || false);

        $this->migrateGameTeams($all || true);

        $this->migrateGameOfficials($all || false);

        echo sprintf("Migrate Games NG2014 Completed.\n");
    }
    /* ======================================================================
     * Migrate Registered Teams
    */
    public function migrateRegTeams($commit)
    {
        if (!$commit) return;

        $this->regTeamConn->delete('regTeams',['projectId' => $this->ng2014ProjectId]);

        $sql = <<<EOD
SELECT 
  keyx       AS keyx,    -- AYSONationalGames2014:AYSO_U10B_Core:01
  orgKey     AS orgView, -- 10-W-68
  name       AS name,
  coach      AS coach,
  points     AS points,
  status     AS status
FROM teams ORDER BY keyx
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $regTeams = [];
        while($row = $stmt->fetch()) {

            $parts      = explode(':',$row['keyx']);
            $projectId  = $parts[0];
            $levelKey   = $parts[1];
            $teamNumber = (integer)$parts[2];

            list($program,$gender,$age,$division) = $this->parseLevelKey($levelKey);

            $orgView = $row['orgView'];
            $parts   = explode('-',$orgView);
            $regionNumber = (integer)$parts[2];
            $orgId = sprintf('AYSOR:%04d',$regionNumber);

            $teamKey = sprintf('%s-%s-%02d',$division,$program,$teamNumber);

            $regTeamId = $projectId . ':' . $teamKey;

            $regTeam = [
                'regTeamId'  => $regTeamId,
                'projectId'  => $projectId,
                'teamKey'    => $teamKey,
                'teamNumber' => $teamNumber,

                'teamName'   => $row['name'],
                'teamPoints' => $row['points'],

                'orgId'    => $orgId,
                'orgView'  => $orgView,

                'program'  => $program,
                'gender'   => $gender,
                'age'      => $age,
                'division' => $division,
            ];
            $this->regTeamConn->insert('regTeams',$regTeam);

            $regTeams[] = $regTeam;

            if ((count($regTeams) % 100) === 0) {
                echo sprintf("\rMigrating Registered Teams %5d",count($regTeams));
            }
        }

        echo sprintf("\rMigrated Registered Teams %5d      \n",count($regTeams));

        file_put_contents('var/regTeams.yml',Yaml::dump($regTeams,9));
    }
    /* ==============================================================
     * Create all the pool teams
     * SELECT teamName,poolKey,poolTeamKey,division,poolTeamId FROM poolTeams WHERE program = 'Core' AND division = 'U14B';
     */
    private function migratePoolTeams($commit)
    {
        if (!$commit) return;

        $this->gameConn->delete('poolTeams',['projectId' => $this->ng2014ProjectId]);

        $sql = <<<EOD
SELECT
  game.projectKey AS projectId, 
  game.levelKey, 
  game.groupType, 
  game.groupName, 
  team.groupSlot,
  team.teamKey,
  team.teamName,
  team.teamPoints
FROM games AS game
LEFT JOIN game_teams AS team ON team.gameId = game.id
ORDER  BY game.levelKey, game.groupType, game.groupName, team.groupSlot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $poolTeams = [];
        while($row = $stmt->fetch()) {

            list($program,$gender,$age,$division) = $this->parseLevelKey($row['levelKey']);

            list(
                $poolTypeKey, $poolKey, $poolTeamKey,
                $poolTypeView,$poolView,$poolTeamView,$poolTeamSlotView
                ) = $this->generatePoolInfo(
                    $program,$gender,$age,$division,
                    $row['groupType'],$row['groupName'],$row['groupSlot']
            );

            $projectId  = $row['projectId'];

            $poolTeamId = $projectId . ':' . $poolTeamKey;

            if (isset($poolTeams[$poolTeamId])) {
                continue;
            }
            $regTeamId = $this->generateRegTeamId($projectId,$row['teamKey'],$program,$division);

            $poolTeam = [
                'poolTeamId' => $poolTeamId,
                'projectId'  => $projectId,

                'poolKey'     => $poolKey,
                'poolTypeKey' => $poolTypeKey,
                'poolTeamKey' => $poolTeamKey,

                'poolView'         => $poolView,
                'poolTypeView'     => $poolTypeView,
                'poolTeamView'     => $poolTeamView,
                'poolTeamSlotView' => $poolTeamSlotView,

                'program'  => $program,
                'gender'   => $gender,
                'age'      => $age,
                'division' => $division,

                'regTeamId'  => $regTeamId,
                'teamName'   => $row['teamName'],
                'teamPoints' => (integer)$row['teamPoints'],
            ];
            $poolTeams[$poolTeamId] = $poolTeam;

            $this->gameConn->insert('poolTeams',$poolTeam);

            if ((count($poolTeams) % 100) === 0) {
                echo sprintf("\rMigrating Pool Teams %5d",count($poolTeams));
            }
        }
        echo sprintf("\rMigrated Pool Teams %5d      \n",count($poolTeams));

        file_put_contents('var/poolTeams.yml',Yaml::dump(array_values($poolTeams),9));
    }
    /* ===================================================================
     * Load up the games with teams
     */
    private function migrateGames($commit)
    {
        if (!$commit) return;

        $this->gameConn->delete('games',['projectId' => $this->ng2014ProjectId]);

        // The games
        $sql = <<<EOD
SELECT
  projectKey AS projectId,
  num        AS gameNumber,
  venueName  AS venueName,
  fieldName  AS fieldName,
  dtBeg      AS start,
  dtEnd      AS finish,
  status     AS status,
  report     AS report
FROM games ORDER BY gameNumber
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $games = [];
        while ($row = $stmt->fetch()) {

            $report = isset($row['report']) ? unserialize($row['report']) : [];

            $game = [
                'gameId'      => sprintf('%s:%s',$row['projectId'],$row['gameNumber']),
                'projectId'   => $row['projectId'],
                'gameNumber'  => $row['gameNumber'],
                'fieldName'   => $row['fieldName'],
                'venueName'   => $row['venueName'],
                'start'       => $row['start'],
                'finish'      => null, // set later when age is known
                'state'       => 'Published',
                'status'      => $row['status'],
                'reportText'  => isset($report['text'])   ? $report['text']   : null,
                'reportState' => isset($report['status']) ? $report['status'] : 'Initial',
            ];
            $this->gameConn->insert('games',$game);

            $games[] = $game;

            if ((count($games) % 100) === 0) {
                echo sprintf("\rMigrating Games %5d",count($games));
            }
        }
        echo sprintf("\rMigrated Games %5d      \n",count($games));

        file_put_contents('var/games.yml',Yaml::dump($games,9));
    }
    private function setGameFinish($gameId,$start,$age)
    {
        // TODO Add this to projects
        $gameLengths = [
            'VIP' => 40 +  5, // Holdover from 2014
            'U10' => 40 +  5,
            'U12' => 50 +  5,
            'U14' => 50 + 10,
            'U16' => 60 + 10,
            'U19' => 60 + 10,
        ];
        $finishDateTime = new \DateTime($start);

        $interval = sprintf('PT%dM',$gameLengths[$age]);

        $finishDateTime->add(new \DateInterval($interval));

        $finish = $finishDateTime->format('Y-m-d H:i:s');

        $this->gameConn->update('games',['finish' => $finish],['gameId' => $gameId]);
    }
    /* ===================================================================
     * Load up the games with teams
     */
    private function migrateGameTeams($commit)
    {
        if (!$commit) return;

        $this->gameConn->delete('gameTeams',['projectId' => $this->ng2014ProjectId]);

        $sql = <<<EOD
SELECT 
  team.*,
  game.projectKey AS projectId,
  game.num        AS gameNumber,
  game.groupType  AS groupType,
  game.groupName  AS groupName,
  game.dtBeg      AS start
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

            $projectId  = $row['projectId'];
            $gameNumber = (integer)$row['gameNumber'];
            $slot       = (integer)$row['slot'];

            $poolTeamId = $projectId . ':' . $poolTeamKey;
            $gameId     = $projectId . ':' . $gameNumber;
            $gameTeamId = $gameId .    ':' . $slot;

            $gameTeam = [
                'gameTeamId' => $gameTeamId,
                'projectId'  => $projectId,
                'gameId'     => $gameId,
                'gameNumber' => $gameNumber,
                'slot'       => $slot,
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

            $this->gameConn->insert('gameTeams',$gameTeam);

            $this->setGameFinish($gameId,$row['start'],$age);

            if ((count($gameTeams) % 100) === 0) {
                echo sprintf("\rMigrating Game Teams %5d",count($gameTeams));
            }
        }
        echo sprintf("\rMigrated Game Teams %5d      \n",count($gameTeams));

        file_put_contents('var/gameTeams.yml',Yaml::dump($gameTeams,9));
    }
    /* ===================================================================
     * Load Game Officials
     */
    private function migrateGameOfficials($commit)
    {
        if (!$commit) return;

        $this->gameConn->delete('gameOfficials',['projectId' => $this->ng2014ProjectId]);

        $sql = <<<EOD
SELECT 
  official.slot           AS slot,
  official.assignRole     AS assignRole,
  official.assignState    AS assignState,
  official.personNameFull AS regPersonName,
  official.personGuid     AS phyPersonId,
  game.projectKey         AS projectId,
  game.num                AS gameNumber
FROM game_officials AS official
LEFT JOIN games AS game ON game.id = official.gameId
ORDER BY gameNumber,official.slot
EOD;
        $stmt = $this->ng2014GamesConn->executeQuery($sql);
        $gameOfficials = [];
        while($row = $stmt->fetch()) {

            $projectId  = $row['projectId'];
            $gameNumber = (integer)$row['gameNumber'];
            $slot       = (integer)$row['slot'];

            $gameId          = $projectId .  ':' . $gameNumber;
            $gameOfficialId  = $gameId .     ':' . $slot;
            $regPersonId     = $projectId .  ':' . $row['phyPersonId'];

            $assignRole = $row['assignRole'];
            $assignRole = $assignRole === 'ROLE_USER' ? 'ROLE_REFEREE' : $assignRole;

            $gameOfficial = [
                'gameOfficialId' => $gameOfficialId,
                'projectId'      => $projectId,
                'gameId'         => $gameId,
                'gameNumber'     => $gameNumber,
                'slot'           => $slot,

                'phyPersonId'    => $row['phyPersonId'],
                'regPersonId'    => $regPersonId,
                'regPersonName'  => $row['regPersonName'],

                'assignRole'     => $assignRole,
                'assignState'    => $row['assignState'],
            ];

            $gameOfficials[] = $gameOfficial;

            $this->gameConn->insert('gameOfficials',$gameOfficial);

            if ((count($gameOfficials) % 100) === 0) {
                echo sprintf("\rMigrating Game Officials %5d",count($gameOfficials));
            }
        }
        echo sprintf("\rMigrated Game Officials %5d      \n",count($gameOfficials));

        file_put_contents('var/gameOfficials.yml',Yaml::dump($gameOfficials,9));
    }
}
