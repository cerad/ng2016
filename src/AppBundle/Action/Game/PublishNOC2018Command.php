<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
//use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class PublishNOC2018Command extends Command
{
    private $reader;

    private $projectId;

    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    private $gameUpdater;
    
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
            ->setName('games:publish:noc2018')
            ->setDescription('Publish Assignments NOC 2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Publishing Assignments NG2018 ...\n");

        $this->gameConn->update('gameOfficials',['assignState' => 'Published'],
            [
                'projectId'   => $this->projectId,
                'assignState' => 'Pending',
            ]);
    }
}
