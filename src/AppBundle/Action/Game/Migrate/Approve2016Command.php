<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule2019\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class Approve2016Command extends Command
{
    private $reader;
    
    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    private $gameUpdater;
    
    private $projectId = 'AYSONationalGames2016';

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

    protected function configure()
    {
        $this
            ->setName('games:approve:ng2019')
            ->setDescription('Adjust Soccerfest Games NG2016')
            ->addArgument('filename',InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approve Soccerfest Games NG2016 ...\n");

        $sql = <<<EOD
SELECT gameOfficialId 
FROM gameOfficials AS gameOfficial
LEFT JOIN games AS game
WHERE game.projectId = ? AND DATE(game.start) = ? AND gameOfficial.assignState = 'Request';
EOD;
        $this->gameConn->update('gameOfficials',['assignState' => 'Approved'],
            [
                'projectId'   => $this->projectId,
                'assignState' => 'Requested',
            ]);
    }
}
