<?php
namespace AppBundle\Action\Game\Admin;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class Publish2018Command extends Command
{
    private $reader;
    
    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    private $gameUpdater;
    
    private $projectId;

    public function __construct(
        $projectId,
        Connection $ng2018GamesConn,
        ScheduleFinder $gameFinder,
        GameUpdater    $gameUpdater,
        RegTeamImportReaderExcel $reader
    ) {
        parent::__construct();

        $this->reader = $reader;

        $this->projectId = $projectId;

        $this->gameConn    = $ng2018GamesConn;
        $this->regTeamConn = $ng2018GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:publish:pending:assignments')
            ->setDescription('Publish Assignments NOC2018')
            ->addOption('date','d',InputOption::VALUE_OPTIONAL,'Publish only by date', '%');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Publishing NOC2018 Assignments ... ");

        $date = $input->getOption('date');

        $sql = "
SELECT gameOfficialId FROM ( 
SELECT 
        DATE(g.start) AS 'date', go.*
    FROM
        noc2018games.gameOfficials go
    RIGHT JOIN noc2018games.games g ON go.gameId = g.gameId) s
WHERE
    projectId LIKE ?
        AND date LIKE ?
        AND assignState = 'Pending';
        ";

        $stmt = $this->gameConn->executeQuery($sql, [$this->projectId, $date]);

        $updated = [];
        while($row = $stmt->fetch()){
            $updated[] = $row;
            $this->gameConn->update('gameOfficials',['assignState' => 'Published'],
            [
                'gameOfficialId' => $row['gameOfficialId'],
                'assignState' => 'Pending'
            ]);
        };
        $count  = count($updated);
        echo sprintf("$count assignments updated.\n");

    }
}
