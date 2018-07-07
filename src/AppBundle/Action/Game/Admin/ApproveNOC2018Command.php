<?php
namespace AppBundle\Action\Game\Admin;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ApproveNOC2018Command extends Command
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
            ->setName('noc2018:approve:game:officials')
            ->setDescription('Approve Game Officials NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving Game Officials NOC2018 ...\n");

        $this->gameConn->update('gameOfficials',['assignState' => 'Approved'],
            [
                'projectId'   => $this->projectId,
                'assignState' => 'Requested',
            ]);
    }
}
