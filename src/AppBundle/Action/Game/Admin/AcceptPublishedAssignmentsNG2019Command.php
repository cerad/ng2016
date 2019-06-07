<?php
namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class AcceptPublishedAssignmentsNG2019Command extends Command
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
            ->setName('ng2019:assignments:accept:published')
            ->setDescription('Approve Assignments Published for Officials NG2019')
            ->addOption('date','d',InputOption::VALUE_OPTIONAL,'Publish only by date', '%');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving NG2019 Assignments Requested by Officials ... ");

        $date = $input->getOption('date');

        $sql = "
SELECT gameOfficialId FROM ( 
SELECT 
        DATE(g.start) AS 'date', go.*
    FROM
        gameOfficials go
    RIGHT JOIN games g ON go.gameId = g.gameId) s
WHERE
    projectId LIKE ?
        AND date LIKE ?
        AND assignState = 'Published';
        ";

        $stmt = $this->gameConn->executeQuery($sql, [$this->projectId, $date]);

        $updated = [];
        while($row = $stmt->fetch()){
            $updated[] = $row;
            $this->gameConn->update('gameOfficials',
                ['assignState' => 'Accepted'],
                [
                    'projectId' => $this->projectId,
                    'gameOfficialId' => $row['gameOfficialId']
                ]);
        }
        $count  = count($updated);
        echo sprintf("$count assignments accepted.\n");

    }
}
