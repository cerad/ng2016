<?php
namespace AppBundle\Action\GameOfficial\Summary;


use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameFinderTrait;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\RegPerson\RegPerson;

class SummaryWriterExcel
{
    private $wb;

    /**
     * @param  RegPerson[] $regPersons
     * @param  Game[]      $games
     * @return string
     * @throws \PHPExcel_Exception
     */
    public function write(array $regPersons, array $games)
    {
        // Not sure this is needed
        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());

        $this->wb = $wb = new \PHPExcel();

        $ws = $wb->getSheet();

        $gameOfficialsMap = $this->generateGameOfficialsMap($games);

        $this->writeSummary($ws, $regPersons,$games,$gameOfficialsMap);
        
        return $this->getContents();
    }

    /**
     * @param  Game[] $games
     * @return array
     */
    private function generateGameOfficialsMap($games)
    {
        $gameOfficialsMap = [];
        foreach($games as $game) {
            foreach($game->getOfficials() as $gameOfficial) {
                if ($gameOfficial->phyPersonId) {
                    $gameOfficial->game = $game;
                    $gameOfficialsMap[$gameOfficial->phyPersonId][] = $gameOfficial;
                }
            }
        }
        return $gameOfficialsMap;
    }
    /**
     * @param  \PHPExcel_Worksheet $ws
     * @param   RegPerson[]  $regPersons
     * @param   Game[]       $games
     * @param   array        $gameOfficials
     * @throws \PHPExcel_Exception
     */
    private function writeSummary(\PHPExcel_Worksheet $ws,$regPersons,$games,$gameOfficialsMap)
    {
        $ws->setTitle('Referee Summary');

        /** @var RegPerson[] $regPersons */
        $regPersons = array_filter($regPersons,function(RegPerson $regPerson)
        {
            //dump($regPerson); die();
            if ($regPerson->isReferee) return true;

            return false;
        });
        usort($regPersons,function(RegPerson $regPerson1, RegPerson $regPerson2)
        {
            return strcmp($regPerson1->name,$regPerson2->name);
        });
        $colRegPersonName  = 'A';
        $colBadge          = 'B';

        $colStatSlotAll  = 'C';
        $colStatSlotRef  = 'D';
        $colStatSlotAr   = 'E';

        $ws->getCell($colRegPersonName . '1')->setValue('Name');
        $ws->getCell($colBadge         . '1')->setValue('Badge');

        $ws->getColumnDimension($colRegPersonName)->setWidth(24);
        $ws->getColumnDimension($colBadge        )->setWidth(12);

        $row = 2;
        foreach($regPersons as $regPerson) {

            $stats = $this->generateStats($regPerson,$gameOfficialsMap);

            $ws->getCell($colRegPersonName . $row)->setValue($regPerson->name);
            $ws->getCell($colBadge         . $row)->setValue($regPerson->refereeBadge);

            if ($stats['slotAll']) {
                $ws->getCell($colStatSlotAll . $row)->setValue($stats['slotAll']);
                $ws->getCell($colStatSlotRef . $row)->setValue($stats['slotRef']);
                $ws->getCell($colStatSlotAr  . $row)->setValue($stats['slotAr' ]);
            }
            $row++;
        }
    }
    private function generateStats(RegPerson $regPerson, $gameOfficialsMap)
    {
        $stats = [
            'slotAll' => 0,
            'slotRef' => 0,
            'slotAr'  => 0,
        ];
        $phyPersonId = $regPerson->personId;
        $gameOfficials = isset($gameOfficialsMap[$phyPersonId]) ? $gameOfficialsMap[$phyPersonId] : null;
        if (!$gameOfficials) {
            return $stats;
        }
        /** @var GameOfficial $gameOfficial */
        foreach($gameOfficials as $gameOfficial) {
            $stats['slotAll']++;
            switch($gameOfficial->slot) {
                case 1:
                    $stats['slotRef']++;
                    break;
                case 2:
                case 3:
                    $stats['slotAr']++;
                    break;
            }
        }
        return $stats;
    }
    private function getContents()
    {
        $writer = \PHPExcel_IOFactory::createWriter($this->wb, "Excel2007");
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
    public function getFileExtension()
    {
        return 'xlsx';
    }
    public function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

}