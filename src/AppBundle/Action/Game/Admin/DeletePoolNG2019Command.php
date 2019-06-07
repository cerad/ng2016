<?php
namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\DBAL\Connection;

class DeletePoolNG2019Command extends Command
{
    private $projectId;
    private $gameConn;

    public function __construct(
        $projectId,
        Connection $ng2019GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn    = $ng2019GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('ng2019:delete:pool')
            ->setDescription('Delete Pool NG2019')
            ->addArgument('poolKey', InputArgument::REQUIRED, 'Pool Key to delete')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Deleting NG2019 Pool ... \n");

        $poolKey = $input->getArgument('poolKey');
        $commit = $input->getOption('commit');

        $gameDeleted = 0;
        $regTeamsDeleted = 0;
        $poolTeamsDeleted = 0;

        $poolTeamIdSELECT = <<<SQL
SELECT 
    DISTINCT pt.poolTeamId
SQL;
        $gameIdSELECT = <<<SQL
SELECT 
    DISTINCT g.gameId
SQL;
        $regTeamIdSELECT = <<<SQL
SELECT 
    DISTINCT pt.regTeamId
SQL;
        $FROM = "
FROM
    ng2019games.poolTeams pt
        LEFT JOIN
    ng2019games.gameTeams gt ON pt.poolTeamId = gt.poolTeamId
    LEFT JOIN
    ng2019games.games g ON gt.gameId = g.gameId
    LEFT JOIN
    ng2019games.gameOfficials go ON g.gameID = go.gameId
WHERE
    pt.projectID = ?
        AND poolKey = ?;
";
        $gameIds = $this->gameConn->executeQuery($gameIdSELECT.$FROM, [$this->projectId, $poolKey]);
        while($row = $gameIds->fetch()){
            if($commit) {
                $this->gameConn->delete(
                    'games',
                    ['gameId' => $row['gameId']]
                );
                $this->gameConn->delete(
                    'gameTeams',
                    ['gameId' => $row['gameId']]
                );
                $this->gameConn->delete(
                    'gameOfficials',
                    ['gameId' => $row['gameId']]
                );
            }
            $gameDeleted += 1;
        }

        $regTeamIds = $this->gameConn->executeQuery($regTeamIdSELECT.$FROM, [$this->projectId, $poolKey]);
        while($row = $regTeamIds->fetch()){
            if($commit) {
                $this->gameConn->delete(
                    'regTeams',
                    ['regTeamId' => $row['regTeamId']]
                );
            }
            $regTeamsDeleted += 1;
        }

        $poolTeamIds = $this->gameConn->executeQuery($poolTeamIdSELECT.$FROM, [$this->projectId, $poolKey]);
        while($row = $poolTeamIds->fetch()){
            if($commit) {
                $this->gameConn->delete(
                    'poolTeams',
                    ['poolTeamId' => $row['poolTeamId']]
                );
            }
            $poolTeamsDeleted += 1;
        }

        if($commit) {
            echo sprintf("%d games deleted.\n", $gameDeleted);
            echo sprintf("%d game teams deleted.\n", 2 * $gameDeleted);
            echo sprintf("%d game officials deleted.\n", 3 * $gameDeleted);
            echo sprintf("%d registered teams deleted.\n", $regTeamsDeleted);
            echo sprintf("%d pool teams deleted.\n", $poolTeamsDeleted);
        } else {
            echo sprintf("%d games will be deleted.\n", $gameDeleted);
            echo sprintf("%d game teams will be deleted.\n", 2 * $gameDeleted);
            echo sprintf("%d game officials will be deleted.\n", 3 * $gameDeleted);
            echo sprintf("%d registered teams will be deleted.\n", $regTeamsDeleted);
            echo sprintf("%d pool teams will be deleted.\n", $poolTeamsDeleted);
            echo sprintf("Use the %s option to commit your changes.\n\n", '-c');

        }
    }
}
