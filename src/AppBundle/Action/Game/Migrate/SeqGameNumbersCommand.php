<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\Game\GameUpdater;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class SeqGameNumbersCommand extends Command
{
    private $gameConn;

    private $gameFinder;
    private $gameUpdater;
    
    private $projectId;

    public function __construct(
        Connection  $ng2019GamesConn,
        GameFinder  $gameFinder,
        GameUpdater $gameUpdater,
        string $projectId
    ) {
        parent::__construct();

        $this->gameConn    = $ng2019GamesConn;
        $this->projectId = $projectId;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
    }

    protected function configure()
    {
        $this
            ->setName('games:sequence:ng2019')
            ->setDescription('Sequence Game Numbers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Sequence Game Numbers\n");

        $this->sequenceGameNumbers('U10B',11001);
        $this->sequenceGameNumbers('U10G',13001);
        $this->sequenceGameNumbers('U12B',11201);
        $this->sequenceGameNumbers('U12G',13201);
        $this->sequenceGameNumbers('U14B',11401);
        $this->sequenceGameNumbers('U14G',13401);
        $this->sequenceGameNumbers('U16B',11601);
        $this->sequenceGameNumbers('U16G',13601);
        $this->sequenceGameNumbers('U19B',11901);
        $this->sequenceGameNumbers('U19G',13901);

    }
    private function sequenceGameNumbers($div,$gameNumber)
    {
        $criteria = [
            'projectIds' => [$this->projectId],
            'programs'   => ['Core'],
            'divisions'  => [$div],
        ];
        $games = $this->gameFinder->findGames($criteria);
        $changeCount = 0;
        //echo sprintf("Seq Game Count %s %u\n",$div,count($games));
        foreach($games as $game) {
            if ($game->gameNumber !== $gameNumber) {
                //echo sprintf("Game number mismatch %d %d\n",$game->gameNumber,$gameNumber);
                $this->gameUpdater->changeGameNumber($this->projectId,$game->gameNumber,$gameNumber);
                $changeCount++;
            }
            $gameNumber++;
        }
        if ($changeCount) {
            echo sprintf("Updated %s %u\n",$div,$changeCount);
        }
    }
 }
