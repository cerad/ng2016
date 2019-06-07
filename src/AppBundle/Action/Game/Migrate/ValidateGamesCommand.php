<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\Game\GameUpdater;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
use Symfony\Component\VarDumper\VarDumper;

class ValidateGamesCommand extends Command
{
    private $gameConn;

    private $gameFinder;
    private $gameUpdater;
    
    private $projectId = 'AYSONationalOpenCup2018';

    public function __construct(
        Connection  $ng2019GamesConn,
        GameFinder  $gameFinder,
        GameUpdater $gameUpdater
    ) {
        parent::__construct();

        $this->gameConn    = $ng2019GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    protected function configure()
    {
        $this
<<<<<<< HEAD
            ->setName('games:validate:ng2019')
=======
            ->setName('validate:games')
>>>>>>> ng2019x2
            ->setDescription('Validate Games');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Validate Games\n");

        $this->validateGameOfficials();
    }
    private function validateGameOfficials()
    {
        $sql = 'SELECT * FROM gameOfficials WHERE projectId = ?';
        $stmt = $this->gameConn->executeQuery($sql,[$this->projectId]);
        while($row = $stmt->fetch()) {
            if ($row['assignState'] === 'Open') {
                if ($row['regPersonId']) {
                    VarDumper::dump($row);
                }
            }
        }
    }
 }
