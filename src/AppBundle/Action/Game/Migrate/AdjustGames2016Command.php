<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule2016\ScheduleFinder;
use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleGameTeam;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setName('games:adjust:ng2019')
            ->setDescription('Adjust Games NG2016')
            ->addArgument('filename',InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Adjust Games NG2016 ...\n");

        /** @noinspection PhpUnusedLocalVariableInspection */
        $filename = $input->getArgument(('filename'));

        //$this->adjust($filename);
        // $this->adjustTimes();
        $this->adjustSoccerfestDuration();
        
    }
    private function adjustSoccerfestDuration()
    {
        $projectId = $this->projectId;
        $sql  = 'SELECT projectId,gameNumber,start FROM games WHERE projectId = ? and DATE(start) = ?';
        $stmt = $this->gameConn->executeQuery($sql,[$projectId,'2016-07-06']);
        while($row = $stmt->fetch()) {

            $finishDateTime = new \DateTime($row['start']);
            $interval = sprintf('PT%dM',50);
            $finishDateTime->add(new \DateInterval($interval));
            $finish =  $finishDateTime->format('Y-m-d H:i:s');

            $this->gameConn->update('games',[
                'finish' => $finish,
            ],[
                'projectId'  => $projectId,
                'gameNumber' => $row['gameNumber'],
            ]);
        }
    }
    private function adjustTimes()
    {
        $this->adjustTime('2016-07-09 08:00:00','2016-07-09 07:30:00');
        $this->adjustTime('2016-07-09 09:15:00','2016-07-09 08:45:00');

        $this->adjustTime('2016-07-09 10:30:00','2016-07-09 10:15:00');
        $this->adjustTime('2016-07-09 11:45:00','2016-07-09 11:30:00');

        $this->adjustTime('2016-07-10 08:00:00','2016-07-10 07:30:00');
        $this->adjustTime('2016-07-10 10:00:00','2016-07-10 09:30:00');
        $this->adjustTime('2016-07-10 13:00:00','2016-07-10 12:30:00');
        $this->adjustTime('2016-07-10 15:00:00','2016-07-10 14:30:00');
    }
    private function adjustTime($dt1,$dt2)
    {
        $projectId = $this->projectId;
        $sql = 'SELECT projectId,gameNumber,start,finish FROM games WHERE projectId = ? and start = ?';
        $stmt = $this->gameConn->executeQuery($sql,[$projectId,$dt1]);
        while($row = $stmt->fetch()) {

            $start1  = new \DateTime($row['start']);
            $finish1 = new \DateTime($row['finish']);
            $interval = $start1->diff($finish1);

            $finish2 = new \DateTime($dt2);
            $finish2->add($interval);
            $dt2x = $finish2->format('Y-m-d H:i:s');

            echo sprintf("Game %d %s %s %s\n",$row['gameNumber'],$interval->format('%H %I'),$dt2,$dt2x);;

            $this->gameConn->update('games',[
                'start'  => $dt2,
                'finish' => $dt2x,
            ],[
                'projectId'  => $projectId,
                'gameNumber' => $row['gameNumber'],
            ]);
        }
    }
    /** @noinspection PhpUnusedPrivateMethodInspection
     *  @param $filename
     */
    private function adjust($filename)
    {
        $this->adjustU10B();
        $this->adjustU14G();
        $this->adjustU16G();
        $this->adjustU16B();
        $this->adjustU19B();

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

    // Moving from 3 pools to two pools
    private function adjustU16B()
    {
        $projectId = $this->projectId;

        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC1');
        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC2');
        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC3');
        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC4');
        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC5');
        $this->deletePoolTeam($projectId . ':' . 'U16BCorePPC6');

        $this->deleteRegTeam($projectId . ':' . 'U16BCore15');
        $this->deleteRegTeam($projectId . ':' . 'U16BCore16');
        $this->deleteRegTeam($projectId . ':' . 'U16BCore17');
        $this->deleteRegTeam($projectId . ':' . 'U16BCore18');

        $poolTeam = [
            'poolTeamId'   => $projectId . ':' . 'U16BCorePPA7',
            'projectId'    => $projectId,
            'poolKey'      => 'U16BCorePPA',
            'poolTypeKey'  => 'PP',
            'poolTeamKey'  => 'U16BCorePPA7',

            'poolView'         => 'U16-B Pool Play A',
            'poolSlotView'     => 'A',
            'poolTypeView'     => 'PP',
            'poolTeamView'     => 'U16-B Pool Play A7',
            'poolTeamSlotView' => 'A7',

            'program'  => 'Core',
            'gender'   => 'B',
            'age'      => 'U16',
            'division' => 'U16B',
        ];
        $this->gameConn->insert('poolTeams',$poolTeam);

        $poolTeam = [
            'poolTeamId'   => $projectId . ':' . 'U16BCorePPB7',
            'projectId'    => $projectId,
            'poolKey'      => 'U16BCorePPB',
            'poolTypeKey'  => 'PP',
            'poolTeamKey'  => 'U16BCorePPB7',

            'poolView'         => 'U16-B Pool Play B',
            'poolSlotView'     => 'B',
            'poolTypeView'     => 'PP',
            'poolTeamView'     => 'U16-B Pool Play B7',
            'poolTeamSlotView' => 'B7',

            'program'  => 'Core',
            'gender'   => 'B',
            'age'      => 'U16',
            'division' => 'U16B',
        ];
        $this->gameConn->insert('poolTeams',$poolTeam);
    }
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
