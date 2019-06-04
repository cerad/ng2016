<?php

namespace AppBundle\Action\Game\Export;

use AppBundle\Action\Schedule\ScheduleGame;
use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GameExportWriterExcel
{
    private $wb;

    /**
     * @param  ScheduleGame[] $games
     * @param string filename
     * @return string
     * @throws PhpSpreadsheet\Exception
     */
    public function write(array $games, $filename = 'php://output')
    {
        // Not sure this is needed
        PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

        $this->wb = $wb = new Spreadsheet();

        $ws = $wb->getSheet(0);

        $this->writeGames($ws, $games);

        return $this->getContents($filename);

    }

    /**
     * @param Worksheet $ws
     * @param ScheduleGame[] $games
     * @throws PhpSpreadsheet\Exception
     */
    private function writeGames(Worksheet $ws, $games)
    {
        $ws->setTitle('Schedule');

        $colProjectId = 'A';
        $colGameNumber = 'B';
        $colDate = 'C';
        $colTime = 'D';
        $colFieldName = 'E';
        $colHomeTeamPoolId = 'F';
        $colHomeTeamName = 'G';
        $colAwayTeamName = 'H';
        $colAwayTeamPoolId = 'I';

        // Column alignment needs to go first?
        $ws->getStyle($colGameNumber)->getAlignment()->setHorizontal(PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($colDate)->getAlignment()->setHorizontal(PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($colTime.'1')->getAlignment()->setHorizontal(PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Not really sure about this ABC stuff but try for now
        $ws->getCell($colProjectId.'1')->setValue('Project ID');
        $ws->getCell($colGameNumber.'1')->setValue('Game');
        $ws->getCell($colDate.'1')->setValue('Date');
        $ws->getCell($colTime.'1')->setValue('Time');
        $ws->getCell($colFieldName.'1')->setValue('Field');
        $ws->getCell($colHomeTeamPoolId.'1')->setValue('Home Team Pool Key');
        $ws->getCell($colHomeTeamName.'1')->setValue('Home Team Name');
        $ws->getCell($colAwayTeamName.'1')->setValue('Away Team Name');
        $ws->getCell($colAwayTeamPoolId.'1')->setValue('Away Team Pool Key');

        $ws->getColumnDimension($colProjectId)->setWidth(24);
        $ws->getColumnDimension($colGameNumber)->setWidth(8);
        $ws->getColumnDimension($colDate)->setWidth(6);
        $ws->getColumnDimension($colTime)->setWidth(10);
        $ws->getColumnDimension($colFieldName)->setWidth(10);
        $ws->getColumnDimension($colHomeTeamPoolId)->setWidth(20);
        $ws->getColumnDimension($colHomeTeamName)->setWidth(30);
        $ws->getColumnDimension($colAwayTeamName)->setWidth(30);
        $ws->getColumnDimension($colAwayTeamPoolId)->setWidth(20);

        // Special formats for date/time
        $rowCount = count($games) + 1;

        $startDateFormatCode = 'ddd';
        $cols = sprintf('%s2:%s%d', $colDate, $colDate, $rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startDateFormatCode);

        $startTimeFormatCode = '[$-409]h:mm AM/PM;@';
        $cols = sprintf('%s2:%s%d', $colTime, $colTime, $rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startTimeFormatCode);

        $row = 2;
        foreach ($games as $game) {

            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

            $ws->getCell($colProjectId.$row)->setValue($game->projectId);
            $ws->getCell($colGameNumber.$row)->setValue($game->gameNumber);

            // Copied from advanced binder
            $startDate = substr($game->start, 0, 10);
            $startDateValue = PhpSpreadsheet\Shared\Date::stringToExcel($startDate);
            $ws->getCell($colDate.$row)->setValueExplicit($startDateValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

            // No built in conversion for time
            $startTime = substr($game->start, 10);
            list($h, $m, $s) = explode(':', $startTime);
            $startTimeValue = $h / 24 + $m / 1440 + $s / 86400;
            $ws->getCell($colTime.$row)->setValueExplicit($startTimeValue, PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

            $ws->getCell($colFieldName.$row)->setValue($game->fieldName);
            $ws->getCell($colHomeTeamPoolId.$row)->setValue($homeTeam->poolTeamKey);
            $ws->getCell($colHomeTeamName.$row)->setValue($homeTeam->regTeamName);
            $ws->getCell($colAwayTeamName.$row)->setValue($awayTeam->regTeamName);
            $ws->getCell($colAwayTeamPoolId.$row)->setValue($awayTeam->poolTeamKey);

            $row++;
        }
    }

    private function getContents($filename)
    {
        $writer = PhpSpreadsheet\IOFactory::createWriter($this->wb, "Xlsx");
        ob_start();
        $writer->save($filename);

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