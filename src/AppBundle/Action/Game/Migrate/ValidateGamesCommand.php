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
    
    private $projectId = 'AYSONationalGames2016';

    public function __construct(
        Connection  $ng2016GamesConn,
        GameFinder  $gameFinder,
        GameUpdater $gameUpdater
    ) {
        parent::__construct();

        $this->gameConn    = $ng2016GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    protected function configure()
    {
        $this
            ->setName('games:validate:ng2016')
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
