<?php
namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class PublishPendingAssignmentsNOC2018Command extends Command
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
            ->setName('noc2018:publish:pending:assignments')
            ->setDescription('Publish Pending Assignments to Officials NOC2018')
            ->addOption('date','d',InputOption::VALUE_OPTIONAL,'Publish only by date', '%');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Publishing NOC2018 Pending Assignments to Officials ... ");

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
            $this->gameConn->update('gameOfficials',
                ['assignState' => 'Published'],
                [
                    'gameOfficialId' => $row['gameOfficialId']
                ]);
        }
        $count  = count($updated);
        echo sprintf("$count assignments published.\n");

    }
}
