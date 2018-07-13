<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class VerifyResultsNOC2018Command extends Command
{
    private $projectId;

    private $gameConn;

    public function __construct(
        $projectId,
        Connection $noc2018GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn = $noc2018GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:verify:results')
            ->setDescription('Verify Match Reports for NOC2018')
            ->addOption('state', 's', InputOption::VALUE_OPTIONAL, 'Verify where reportState is xxx', 'Submitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Verify Match Reports ... ");

        $reportState = $input->getOption('state');

        $this->gameConn->update(
            'games',
            ['reportState' => 'Verified'],
            [
                'projectId' => $this->projectId,
                'reportState' => $reportState,
            ]
        );

        echo sprintf(" verified.\n");

    }
}
