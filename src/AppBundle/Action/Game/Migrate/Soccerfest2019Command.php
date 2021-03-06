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

class Soccerfest2019Command extends Command
{
    private $reader;
    
    private $gameConn;
    private $regTeamConn;
    
    private $gameFinder;
    private $gameUpdater;
    
    private $projectId;

    public function __construct(
        Connection $ng2019GamesConn,
        ScheduleFinder $gameFinder,
        GameUpdater    $gameUpdater,
        RegTeamImportReaderExcel $reader,
        String $projecctId
    ) {
        parent::__construct();

        $this->reader = $reader;
        
        $this->gameConn    = $ng2019GamesConn;
        $this->regTeamConn = $ng2019GamesConn;
        
        $this->gameFinder  = $gameFinder;
        $this->gameUpdater = $gameUpdater;

        $this->projectId = $projecctId;
    }

    protected function configure()
    {
        $this
            ->setName('games:soccerfest:ng2019')
            ->setDescription('Adjust Soccerfest Games NG2016')
            ->addArgument('filename',InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Adjust Soccerfest Games NG2016 ...\n");

        /** @noinspection PhpUnusedLocalVariableInspection */
        $filename = $input->getArgument(('filename'));

        $this->addSoccerfestPoolTeams('B10U',24);
        $this->addSoccerfestPoolTeams('G12U',24);
        $this->addSoccerfestPoolTeams('B12U',24);
        $this->addSoccerfestPoolTeams('B14U',24);
        $this->addSoccerfestPoolTeams('G14U',24);
        $this->addSoccerfestPoolTeams('G16U',24);
        $this->addSoccerfestPoolTeams('B19U',24);

        $this->addSoccerfestPoolTeams('U10G',18);
        $this->addSoccerfestPoolTeams('G19U',18);

        $this->addSoccerfestPoolTeams('B16U',14);

        foreach(['U10G','B10U','G12U','B12U','G14U','B14U','G16U','B16U','G19U','B19U'] as $div) {
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
    private function adjustB16U()
    {
        $projectId = $this->projectId;

        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC1');
        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC2');
        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC3');
        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC4');
        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC5');
        $this->deletePoolTeam($projectId . ':' . 'B16UCorePPC6');

        $this->deleteRegTeam($projectId . ':' . 'B16UCore15');
        $this->deleteRegTeam($projectId . ':' . 'B16UCore16');
        $this->deleteRegTeam($projectId . ':' . 'B16UCore17');
        $this->deleteRegTeam($projectId . ':' . 'B16UCore18');

        $poolTeam = [
            'poolTeamId'   => $projectId . ':' . 'B16UCorePPA7',
            'projectId'    => $projectId,
            'poolKey'      => 'B16UCorePPA',
            'poolTypeKey'  => 'PP',
            'poolTeamKey'  => 'B16UCorePPA7',

            'poolView'         => 'B16U Pool Play A',
            'poolSlotView'     => 'A',
            'poolTypeView'     => 'PP',
            'poolTeamView'     => 'B16U Pool Play A7',
            'poolTeamSlotView' => 'A7',

            'program'  => 'Core',
            'gender'   => 'B',
            'age'      => 'U16',
            'division' => 'B16U',
        ];
        $this->gameConn->insert('poolTeams',$poolTeam);

        $poolTeam = [
            'poolTeamId'   => $projectId . ':' . 'B16UCorePPB7',
            'projectId'    => $projectId,
            'poolKey'      => 'B16UCorePPB',
            'poolTypeKey'  => 'PP',
            'poolTeamKey'  => 'B16UCorePPB7',

            'poolView'         => 'B16U Pool Play B',
            'poolSlotView'     => 'B',
            'poolTypeView'     => 'PP',
            'poolTeamView'     => 'B16U Pool Play B7',
            'poolTeamSlotView' => 'B7',

            'program'  => 'Core',
            'gender'   => 'B',
            'age'      => 'U16',
            'division' => 'B16U',
        ];
        $this->gameConn->insert('poolTeams',$poolTeam);
    }
}
