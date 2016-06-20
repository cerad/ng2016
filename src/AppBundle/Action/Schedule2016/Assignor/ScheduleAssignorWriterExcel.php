<?php
namespace AppBundle\Action\Schedule2016\Assignor;

use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleGameOfficial;

class ScheduleAssignorWriterExcel
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

        $colGameNumber     = 'A';
        $colDate           = 'B';
        $colTime           = 'C';
        $colFieldName      = 'D';
        $colHomeTeamPoolId = 'E';
        $colHomeTeamName   = 'F';
        $colAwayTeamName   = 'G';
        $colAwayTeamPoolId = 'H';

        $colRef = 'I';
        $colAR1 = 'J';
        $colAR2 = 'K';

        // Column alignment needs to go first?
        $ws->getStyle($colGameNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($colDate)      ->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($colTime . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Not really sure about this ABC stuff but try for now
        $ws->getCell($colGameNumber     . '1')->setValue('Game');
        $ws->getCell($colDate           . '1')->setValue('Date');
        $ws->getCell($colTime           . '1')->setValue('Time');
        $ws->getCell($colFieldName      . '1')->setValue('Field');
        $ws->getCell($colHomeTeamPoolId . '1')->setValue('Home Team Pool');
        $ws->getCell($colHomeTeamName   . '1')->setValue('Home Team Name');
        $ws->getCell($colAwayTeamName   . '1')->setValue('Away Team Name');
        $ws->getCell($colAwayTeamPoolId . '1')->setValue('Away Team Pool');

        $ws->getCell($colRef . '1')->setValue('Referee');
        $ws->getCell($colAR1 . '1')->setValue('Asst Ref 1');
        $ws->getCell($colAR2 . '1')->setValue('Asst Ref 2');

        $ws->getColumnDimension($colGameNumber    )->setWidth( 8);
        $ws->getColumnDimension($colDate          )->setWidth( 6);
        $ws->getColumnDimension($colTime          )->setWidth(10);
        $ws->getColumnDimension($colFieldName     )->setWidth(10);
        $ws->getColumnDimension($colHomeTeamPoolId)->setWidth(20);
        $ws->getColumnDimension($colHomeTeamName  )->setWidth(30);
        $ws->getColumnDimension($colAwayTeamName  )->setWidth(30);
        $ws->getColumnDimension($colAwayTeamPoolId)->setWidth(20);

        $ws->getColumnDimension($colRef)->setWidth(20);
        $ws->getColumnDimension($colAR1)->setWidth(20);
        $ws->getColumnDimension($colAR2)->setWidth(20);

        // Special formats for date/time
        $rowCount = count($games) + 1;
        
        $startDateFormatCode = 'ddd';
        $cols = sprintf('%s2:%s%d',$colDate,$colDate,$rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startDateFormatCode);
        
        $startTimeFormatCode = '[$-409]h:mm AM/PM;@';
        $cols = sprintf('%s2:%s%d',$colTime,$colTime,$rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startTimeFormatCode);

        $row = 2;
        foreach($games as $game) {
            
            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

            $ws->getCell($colGameNumber . $row)->setValueExplicit($game->gameNumber);

            // Copied from advanced binder
            $startDate = substr($game->start,0,10);
            $startDateValue = \PHPExcel_Shared_Date::stringToExcel($startDate);
            $ws->getCell($colDate . $row)->setValueExplicit($startDateValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // No built in conversion for time
            $startTime = substr($game->start,10);
            list($h, $m, $s) = explode(':', $startTime);
            $startTimeValue = $h / 24 + $m / 1440 + $s / 86400;
            $ws->getCell($colTime . $row)->setValueExplicit($startTimeValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            
            $ws->getCell($colFieldName      . $row)->setValue($game->fieldName);
            $ws->getCell($colHomeTeamPoolId . $row)->setValue($homeTeam->poolTeamView);
            $ws->getCell($colHomeTeamName   . $row)->setValue($homeTeam->regTeamName);
            $ws->getCell($colAwayTeamName   . $row)->setValue($awayTeam->regTeamName);
            $ws->getCell($colAwayTeamPoolId . $row)->setValue($awayTeam->poolTeamView);

            $gameOfficial = $game->referee;
            $ws->getCell($colRef . $row)->setValue($this->getGameOfficialValue($gameOfficial));

            $gameOfficial = $game->ar1;
            $ws->getCell($colAR1 . $row)->setValue($this->getGameOfficialValue($gameOfficial));

            $gameOfficial = $game->ar2;
            $ws->getCell($colAR2 . $row)->setValue($this->getGameOfficialValue($gameOfficial));

            $row++;
        }
    }
    private function getGameOfficialValue(ScheduleGameOfficial $gameOfficial)
    {
        return $gameOfficial->regPersonName;
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