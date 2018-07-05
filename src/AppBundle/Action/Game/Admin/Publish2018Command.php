<?php
namespace AppBundle\Action\Game\Admin;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setName('noc2018:publish:assignments')
            ->setDescription('Publish Assignments NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Publish Assignments NOC2018 ...\n");

        $this->gameConn->update('gameOfficials',['assignState' => 'Published'],
            [
                'projectId'   => $this->projectId,
                'assignState' => 'Pending',
            ]);
    }
}
