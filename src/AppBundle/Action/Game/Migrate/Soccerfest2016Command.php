<?php
namespace AppBundle\Action\Game\Migrate;

use AppBundle\Action\Game\GameUpdater;
use AppBundle\Action\RegTeam\Import\RegTeamImportReaderExcel;
use AppBundle\Action\Schedule2016\ScheduleFinder;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class Soccerfest2016Command extends Command
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
            ->setName('games:soccerfest:ng2016')
            ->setDescription('Adjust Soccerfest Games NG2016')
            ->addArgument('filename',InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Adjust Soccerfest Games NG2016 ...\n");

        /** @noinspection PhpUnusedLocalVariableInspection */
        $filename = $input->getArgument(('filename'));

        $this->addSoccerfestPoolTeams('U10B',24);
        $this->addSoccerfestPoolTeams('U12G',24);
        $this->addSoccerfestPoolTeams('U12B',24);
        $this->addSoccerfestPoolTeams('U14B',24);
        $this->addSoccerfestPoolTeams('U14G',24);
        $this->addSoccerfestPoolTeams('U16G',24);
        $this->addSoccerfestPoolTeams('U19B',24);

        $this->addSoccerfestPoolTeams('U10G',18);
        $this->addSoccerfestPoolTeams('U19G',18);

        $this->addSoccerfestPoolTeams('U16B',14);

        foreach(['U10G','U10B','U12G','U12B','U14G','U14B','U16G','U16B','U19G','U19B'] as $div) {
            $this->deleteOldPoolTeams($div);
        }
    }
    private function addSoccerfestPoolTeams($div,$qty)
    {
        $projectId = $this->projectId;
        $age    = substr($div,0,3);
        $gender = substr($div,3);

        $poolView1     = null;
        $poolSlotView1 = null;

        $poolView2     = null;
        $poolSlotView2 = null;

        switch($qty) {
            case 14:
                $poolView1 = sprintf('%s-%s Soccerfest 01-08',$age,$gender);
                $poolSlotView1 = '01-08';

                $poolView2 = sprintf('%s-%s Soccerfest 09-14',$age,$gender);
                $poolSlotView2 = '09-14';
                break;
            case 18:
                $poolView1 = sprintf('%s-%s Soccerfest 01-12',$age,$gender);
                $poolSlotView1 = '01-12';

                $poolView2 = sprintf('%s-%s Soccerfest 13-18',$age,$gender);
                $poolSlotView2 = '13-18';
                break;
            case 24:
                $poolView1 = sprintf('%s-%s Soccerfest 01-12',$age,$gender);
                $poolSlotView1 = '01-12';

                $poolView2 = sprintf('%s-%s Soccerfest 13-24',$age,$gender);
                $poolSlotView2 = '13-24';
                break;
        }

        for($num  = 1; $num <= $qty; $num++) {

            $poolTeamKey  = sprintf('%sCoreZZ%02u',$div,$num);
            $poolTeamView = sprintf('%s-%s Soccerfest Team %2u',$age,$gender,$num);

            if ($num <= 12) {
                $poolKey      = $div . 'CoreZZ1';
                $poolView     = $poolView1;
                $poolSlotView = $poolSlotView1;
            } else {
                $poolKey      = $div . 'CoreZZ2';
                $poolView     = $poolView2;
                $poolSlotView = $poolSlotView2;
            }
            if ($qty === 14) {
                if ($num <= 8) {
                    $poolKey      = $div . 'CoreZZ1';
                    $poolView     = $poolView1;
                    $poolSlotView = $poolSlotView1;
                } else {
                    $poolKey      = $div . 'CoreZZ2';
                    $poolView     = $poolView2;
                    $poolSlotView = $poolSlotView2;
                }
            }
            $poolTeam = [
                'poolTeamId'  => $projectId . ':' . $poolTeamKey,
                'projectId'   => $projectId,
                'poolKey'     => $poolKey,
                'poolTypeKey' => 'ZZ',
                'poolTeamKey' => $poolTeamKey,

                'poolView'     => $poolView,
                'poolSlotView' => $poolSlotView,
                'poolTypeView' => 'SOF',
                'poolTeamView' => $poolTeamView,
                'poolTeamSlotView' => sprintf('Team %2u',$num),

                'program'  => 'Core',
                'gender'   => $gender,
                'age'      => $age,
                'division' => $div,
            ];
            $this->gameConn->insert('poolTeams', $poolTeam);
        }
    }
    private function deleteOldPoolTeams($div)
    {
        $this->deletePoolTeam($div . 'CoreZZ01-12X');
        $this->deletePoolTeam($div . 'CoreZZ01-12Y');
        $this->deletePoolTeam($div . 'CoreZZ13-24X');
        $this->deletePoolTeam($div . 'CoreZZ13-24Y');
    }
    private function deletePoolTeam($poolTeamKey)
    {
        $poolTeamId = $this->projectId . ':' . $poolTeamKey;
        $id = ['poolTeamId' => $poolTeamId];
        $this->gameConn->delete('poolTeams',$id);
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
}
