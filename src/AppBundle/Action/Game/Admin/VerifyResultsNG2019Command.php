<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class VerifyResultsNG2019Command extends Command
{
    private $projectId;

    private $gameConn;

    public function __construct(
        $projectId,
        Connection $ng2019GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn = $ng2019GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('ng2019:verify:results')
            ->setDescription('Verify Match Reports for NG2019')
            ->addOption('state', 's', InputOption::VALUE_OPTIONAL, 'Verify where reportState is xxx', 'Submitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Verify NG2019 Match Reports ... ");

        $reportState = $input->getOption('state');

        $result = $this->gameConn->executeQuery("SELECT * FROM games WHERE `projectId` = ? AND reportState = 'Verified'",[$this->projectId]);
        $reportCount = count($result->fetchAll());

        $result = $this->gameConn->executeQuery("SELECT * FROM games WHERE projectId = ? AND reportState = 'Verified'", [$this->projectId]);
        $countPre = count($result->fetchAll());

        $this->gameConn->update(
            'games',
            ['reportState' => 'Verified'],
            [
                'projectId' => $this->projectId,
                'reportState' => $reportState,
            ]
        );

        $result = $this->gameConn->executeQuery("SELECT * FROM games WHERE projectId = ? AND reportState = 'Verified'", [$this->projectId]);
        $countPost = count($result->fetchAll());

        echo sprintf("%d verified of %d match reports submitted.\n", abs($countPost-$countPre), $reportCount);

    }
}
