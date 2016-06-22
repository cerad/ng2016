<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule2016\ScheduleFinder;
use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleGameTeam;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class AdjustGames2016Command extends Command
{
    private $reader;
    
    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    
    private $projectId = 'AYSONationalGames2016';

    public function __construct(
        Connection $ng2016GamesConn,
        ScheduleFinder $gameFinder,
        RegTeamImportReaderExcel $reader
    ) {
        parent::__construct();

        $this->reader = $reader;
        
        $this->gameConn    = $ng2016GamesConn;
        $this->regTeamConn = $ng2016GamesConn;
        
        $this->gameFinder = $gameFinder;
    }

    protected function configure()
    {
        $this
            ->setName('adjust:games:ng2016')
            ->setDescription('Adjust Games NG2016')
            ->addArgument('filename');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Adjust Games NG2016 ...\n");

        $this->adjustU10B();

        $filename = $input->getArgument(('filename'));
        foreach(['U10B','U10G'] as $div) {
            $this->processFile($filename,$div);
        }

    }
    private function adjustU10B()
    {
        $projectId = $this->projectId;
        $poolTeamIdB6 =  $projectId . ':' . 'U10BCorePPB6';
        $poolTeamIdD6 =  $projectId . ':' . 'U10BCorePPD6';

        // Remove the pool teams
        $this->deletePoolTeam($poolTeamIdB6);
        $this->deletePoolTeam($poolTeamIdD6);

        // Remove the reg teams
        $this->deleteRegTeam($projectId . ':' . 'U10BCore23');
        $this->deleteRegTeam($projectId . ':' . 'U10BCore24');

        // Find the impacted games
        $gamesB6 = $this->gameFinder->findGames(['poolTeamIds' => [$poolTeamIdB6]]);
        $gamesD6 = $this->gameFinder->findGames(['poolTeamIds' => [$poolTeamIdD6]]);

        echo sprintf("Games %d %d\n",count($gamesB6),count($gamesD6));
        if (count($gamesB6) !== count($gamesD6)) {
            return;
        }
        for($i = 0; $i < count($gamesB6); $i++) {
            $this->mergeGames($poolTeamIdB6,$poolTeamIdD6,$gamesB6[$i],$gamesD6[$i]);
        }
    }
    private function mergeGames($poolTeamId1,$poolTeamId2,ScheduleGame $game1, ScheduleGame $game2)
    {
        // Start times should match
        if ($game1->start != $game2->start) {
            sprintf("*** Merging games with different start times\n");
        }
        // Find the team to merge
        $poolTeamId2x = null;
        if ($game2->homeTeam->poolTeamId === $poolTeamId2) {
            $poolTeamId2x = $game2->awayTeam->poolTeamId;
        }
        if ($game2->awayTeam->poolTeamId === $poolTeamId2) {
            $poolTeamId2x = $game2->homeTeam->poolTeamId;
        }
        if (!$poolTeamId2x) {
            echo sprintf("*** Cannot find team to merge\n");
        }
        // echo sprintf("Merge %s\n",$poolTeamId2x);
        // Merge it
        if ($game1->homeTeam->poolTeamId === $poolTeamId1) {
            $this->changePoolTeam($game1->homeTeam,$poolTeamId2x);
        }
        if ($game1->awayTeam->poolTeamId === $poolTeamId1) {
            $this->changePoolTeam($game1->awayTeam,$poolTeamId2x);
        }
        // Remove the second game
        $this->deleteGame($game2);

    }
    private function deleteGame(ScheduleGame $game)
    {
        $id = ['gameId' => $game->gameId];
        $this->gameConn->delete('gameTeams',    $id);
        $this->gameConn->delete('gameOfficials',$id);
        $this->gameConn->delete('games',        $id);
    }
    private function deletePoolTeam($poolTeamId)
    {
        $id = ['poolTeamId' => $poolTeamId];
        $this->gameConn->delete('poolTeams',$id);
    }
    private function deleteRegTeam($regTeamId)
    {
        $id = ['regTeamId' => $regTeamId];
        $this->gameConn->delete('regTeams',$id);
    }
    private function changePoolTeam(ScheduleGameTeam $gameTeam,$poolTeamId)
    {
        $this->gameConn->update('gameTeams',
            ['poolTeamId' => $poolTeamId],
            ['gameTeamId' => $gameTeam->gameTeamId]);
    }
    private function processFile($filename,$div)
    {
        $regTeams = $this->reader->read($filename,$div);

        echo sprintf("Processing %s %d\n",$div,count($regTeams));

        foreach($regTeams as $regTeam) {
            $this->processRegTeam($regTeam);
        }
        
    }
    private function processRegTeam($regTeam)
    {
        $regTeamId = $this->projectId . ':' . $regTeam['regTeamKey'];
        $updates = [
            'teamName' => $regTeam['regTeamName'],
            'orgId'    => $regTeam['orgId'],
            'orgView'  => $regTeam['orgView'],
        ];
        $this->regTeamConn->update('regTeams',$updates,['regTeamId' => $regTeamId]);

        // Only process the pool play team for now
        $poolTeamId = $this->projectId . ':' . $regTeam['poolTeamKeys'][0];

        $updates = [
            'regTeamId'   => $regTeamId,
            'regTeamName' => $regTeam['regTeamName'],
        ];
        $this->gameConn->update('poolTeams',$updates,['poolTeamId' => $poolTeamId]);
    }
}
