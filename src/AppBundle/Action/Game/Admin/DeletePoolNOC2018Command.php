<?php
namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\DBAL\Connection;

class DeletePoolNOC2018Command extends Command
{
    private $projectId;
    private $gameConn;

    public function __construct(
        $projectId,
        Connection $noc2018GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn    = $noc2018GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:delete:pool')
            ->setDescription('Delete Pool NOC2018')
            ->addArgument('poolKey', InputArgument::REQUIRED, 'Pool Key to delete')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving NOC2018 Assignments Requested by Officials ... \n");

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
    noc2018games.poolTeams pt
        LEFT JOIN
    noc2018games.gameTeams gt ON pt.poolTeamId = gt.poolTeamId
    LEFT JOIN
    noc2018games.games g ON gt.gameId = g.gameId
    LEFT JOIN
    noc2018games.gameOfficials go ON g.gameID = go.gameId
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
