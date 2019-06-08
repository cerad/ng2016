<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL;

class Publish2016Command extends Command
{
    /**
     * @var RegTeamImportReaderExcel
     */
    private $reader;

    /**
     * @var Connection
     */
    private $gameConn;
    /**
     * @var Connection
     */
    private $regTeamConn;

    /**
     * @var ScheduleFinder
     */
    private $gameFinder;
    /**
     * @var GameUpdater
     */
    private $gameUpdater;

    /**
     * @var string
     */
    private $projectId = 'AYSONationalOpenCup2017';

    /**
     * Publish2016Command constructor.
     * @param Connection $ng2019GamesConn
     * @param ScheduleFinder $gameFinder
     * @param GameUpdater $gameUpdater
     * @param RegTeamImportReaderExcel $reader
     */
    public function __construct(
        Connection $ng2019GamesConn,
        ScheduleFinder $gameFinder,
        GameUpdater    $gameUpdater,
        RegTeamImportReaderExcel $reader
    ) {
        parent::__construct();

        $this->reader = $reader;
        
        $this->gameConn    = $ng2019GamesConn;
        $this->regTeamConn = $ng2019GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('games:publish:ng2019')
            ->setDescription('Publish Assignments NG2016')
            ->addArgument('filename',InputArgument::OPTIONAL);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Publish Assignments NG2016 ...\n");

        $this->gameConn->update('gameOfficials',['assignState' => 'Published'],
            [
                'projectId'   => $this->projectId,
                'assignState' => 'Pending',
            ]);
    }
}
