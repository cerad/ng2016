<?php
namespace AppBundle\Action\Game\Admin;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ApproveVerifiedNOC2018Command extends Command
{
    private $reader;

    private $projectId;

    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    private $gameUpdater;
    
    public function __construct(
        $projectId,
        Connection $noc2018Conn,
        ScheduleFinder $gameFinder,
        GameUpdater    $gameUpdater,
        RegTeamImportReaderExcel $reader
    ) {
        parent::__construct();

        $this->reader = $reader;

        $this->projectId = $projectId;
        $this->gameConn    = $noc2018Conn;

        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:approve:verified:officials')
            ->setDescription('Approve Verified Game Officials NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving Verified Game Officials NOC2018 ...\n");

        $this->gameConn->update('projectPersonRoles',['approved' => '1', ],
            [
                'projectId'   => $this->projectId,
                'verified' => '1',
            ]);
    }
}
