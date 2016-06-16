<?php
namespace AppBundle\Action\Game\Export;

use AppBundle\Action\Schedule2016\ScheduleGame;

class GameWriterExcel
{
    private $wb;

    /**
     * @param  ScheduleGame[] $games
     * @return string
     * @throws \PHPExcel_Exception
     */
    public function write(array $games)
    {
        // Not sure this is needed
        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());

        $this->wb = $wb = new \PHPExcel();

        $ws = $wb->getSheet();

        $this->writeGames($ws, $games);
        
        return $this->getContents();
        
    }

    /**
     * @param \PHPExcel_Worksheet $ws
     * @param ScheduleGame[] $games
     * @throws \PHPExcel_Exception
     */
    private function writeGames(\PHPExcel_Worksheet $ws,$games)
    {
        $ws->setTitle('Schedule');

        // Column alignment needs to go first?
        $ws->getStyle('A' )->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('B' )->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('C1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Not really sure about this ABC stuff but try for now
        $ws->getCell('A1')->setValue('Game');
        $ws->getCell('B1')->setValue('Date');
        $ws->getCell('C1')->setValue('Time');
        $ws->getCell('D1')->setValue('Field');
        $ws->getCell('E1')->setValue('Home Team Pool');
        $ws->getCell('F1')->setValue('Home Team Name');
        $ws->getCell('G1')->setValue('Away Team Name');
        $ws->getCell('H1')->setValue('Away Team Pool');

        $ws->getColumnDimension('A')->setWidth( 8);
        $ws->getColumnDimension('B')->setWidth( 6);
        $ws->getColumnDimension('C')->setWidth(10);
        $ws->getColumnDimension('D')->setWidth(10);
        $ws->getColumnDimension('E')->setWidth(20);
        $ws->getColumnDimension('F')->setWidth(30);
        $ws->getColumnDimension('G')->setWidth(30);
        $ws->getColumnDimension('H')->setWidth(20);

        // Special formats for date/time
        $rowCount = count($games) + 1;
        
        $startDateFormatCode = 'ddd';
        $ws->getStyle('B2:B' . $rowCount)->getNumberFormat()->setFormatCode($startDateFormatCode);
        
        $startTimeFormatCode = '[$-409]h:mm AM/PM;@';
        $ws->getStyle('C2:C'. $rowCount)->getNumberFormat()->setFormatCode($startTimeFormatCode);

        $row = 2;
        foreach($games as $game) {
            
            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;
            
            $ws->getCell('A' . $row)->setValue($game->gameNumber);

            // Copied from advanced binder
            $startDate = substr($game->start,0,10);
            $startDateValue = \PHPExcel_Shared_Date::stringToExcel($startDate);
            $ws->getCell('B' . $row)->setValueExplicit($startDateValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // No built in conversion for time
            $startTime = substr($game->start,10);
            list($h, $m, $s) = explode(':', $startTime);
            $startTimeValue = $h / 24 + $m / 1440 + $s / 86400;
            $ws->getCell('C' . $row)->setValueExplicit($startTimeValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            
            $ws->getCell('D' . $row)->setValue($game->fieldName);
            $ws->getCell('E' . $row)->setValue($homeTeam->poolTeamKey);
            $ws->getCell('F' . $row)->setValue($homeTeam->regTeamName);
            $ws->getCell('G' . $row)->setValue($awayTeam->regTeamName);
            $ws->getCell('H' . $row)->setValue($awayTeam->poolTeamKey);
            
            $row++;
        }
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