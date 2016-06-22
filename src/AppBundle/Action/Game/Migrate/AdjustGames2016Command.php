<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
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
    private $gameUpdater;
    
    private $projectId = 'AYSONationalGames2016';

    public function __construct(
        Connection $ng2016GamesConn,
        ScheduleFinder $gameFinder,
        GameUpdater    $gameUpdater,
        RegTeamImportReaderExcel $reader
    ) {
        parent::__construct();

        $this->reader = $reader;
        
        $this->gameConn    = $ng2016GamesConn;
        $this->regTeamConn = $ng2016GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;
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
        $this->adjustU14G();
        $this->adjustU16G();
        $this->adjustU19B();

        $filename = $input->getArgument(('filename'));
        foreach(['U10B','U10G','U12B','U12G','U14B','U14G','U16B','U16G','U19B','U19G'] as $div) {
            $this->processFile($filename,$div);
        }

    }
    // Currently down by 4 teams
    private function adjustU14G()
    {
        $projectId = $this->projectId;
        $poolTeamIdA6 =  $projectId . ':' . 'U14GCorePPA6';
        $poolTeamIdB6 =  $projectId . ':' . 'U14GCorePPB6';
        $poolTeamIdC6 =  $projectId . ':' . 'U14GCorePPC6';
        $poolTeamIdD6 =  $projectId . ':' . 'U14GCorePPD6';

        $this->deletePoolTeam($poolTeamIdA6);
        $this->deletePoolTeam($poolTeamIdB6);
        $this->deletePoolTeam($poolTeamIdC6);
        $this->deletePoolTeam($poolTeamIdD6);

        $this->deleteRegTeam($projectId . ':' . 'U14GCore21');
        $this->deleteRegTeam($projectId . ':' . 'U14GCore22');
        $this->deleteRegTeam($projectId . ':' . 'U14GCore23');
        $this->deleteRegTeam($projectId . ':' . 'U14GCore24');

        $this->crossPool($poolTeamIdA6,$poolTeamIdC6);
        $this->crossPool($poolTeamIdB6,$poolTeamIdD6);
    }
    // Currently down by 4 teams
    private function adjustU16G()
    {
        $projectId = $this->projectId;
        $poolTeamIdA6 =  $projectId . ':' . 'U16GCorePPA6';
        $poolTeamIdB6 =  $projectId . ':' . 'U16GCorePPB6';
        $poolTeamIdC6 =  $projectId . ':' . 'U16GCorePPC6';
        $poolTeamIdD6 =  $projectId . ':' . 'U16GCorePPD6';

        $this->deletePoolTeam($poolTeamIdA6);
        $this->deletePoolTeam($poolTeamIdB6);
        $this->deletePoolTeam($poolTeamIdC6);
        $this->deletePoolTeam($poolTeamIdD6);

        $this->deleteRegTeam($projectId . ':' . 'U16GCore21');
        $this->deleteRegTeam($projectId . ':' . 'U16GCore22');
        $this->deleteRegTeam($projectId . ':' . 'U16GCore23');
        $this->deleteRegTeam($projectId . ':' . 'U16GCore24');

        $this->crossPool($poolTeamIdA6,$poolTeamIdC6);
        $this->crossPool($poolTeamIdB6,$poolTeamIdD6);

    }
    // Currently down by 4 teams
    private function adjustU19B()
    {
        $projectId = $this->projectId;
        $poolTeamIdA6 =  $projectId . ':' . 'U19BCorePPA6';
        $poolTeamIdB6 =  $projectId . ':' . 'U19BCorePPB6';
        $poolTeamIdC6 =  $projectId . ':' . 'U19BCorePPC6';
        $poolTeamIdD6 =  $projectId . ':' . 'U19BCorePPD6';
        
        $this->deletePoolTeam($poolTeamIdA6);
        $this->deletePoolTeam($poolTeamIdB6);
        $this->deletePoolTeam($poolTeamIdC6);
        $this->deletePoolTeam($poolTeamIdD6);

        $this->deleteRegTeam($projectId . ':' . 'U19BCore21');
        $this->deleteRegTeam($projectId . ':' . 'U19BCore22');
        $this->deleteRegTeam($projectId . ':' . 'U19BCore23');
        $this->deleteRegTeam($projectId . ':' . 'U19BCore24');

        $this->crossPool($poolTeamIdA6,$poolTeamIdC6);
        $this->crossPool($poolTeamIdB6,$poolTeamIdD6);
    }
    // Down by two teams
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
        $this->crossPool($poolTeamIdB6,$poolTeamIdD6);

    }
    private function crossPool($poolTeamId1,$poolTeamId2)
    {
        $games1 = $this->gameFinder->findGames(['poolTeamIds' => [$poolTeamId1]]);
        $games2 = $this->gameFinder->findGames(['poolTeamIds' => [$poolTeamId2]]);

        if (count($games1) !== count($games2)) {
            return;
        }
        for($i = 0; $i < count($games1); $i++) {
            $this->mergeGames($poolTeamId1,$poolTeamId2,$games1[$i],$games2[$i]);
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
        $this->gameUpdater->deleteGame($game2->projectId,$game2->gameNumber);

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
