<?php
namespace AppBundle\Action\Schedule2016\Assignor;

use AppBundle\Action\GameOfficial\GameOfficialDetailsFinder;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\ShirtSizeTransformer;
use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleGameOfficial;

class ScheduleAssignorWriterExcel
{
    private $wb;

    private $colGameNumber     = 'A';
    private $colDate           = 'B';
    private $colTime           = 'C';
    private $colFieldName      = 'D';
    private $colHomeTeamPoolId = 'E';
    private $colHomeTeamName   = 'F';
    private $colAwayTeamName   = 'G';
    private $colAwayTeamPoolId = 'H';

    private $colSlot    = 'I';
    private $colState   = 'J';
    private $colName    = 'K';
    private $colOrgView = 'L';
    private $colBadge   = 'M';
    private $colAge     = 'N';
    private $colShirt   = 'O';
    private $colPhone   = 'P';
    private $colEmail   = 'Q';

    private $assignWorkflow;
    private $phoneTransformer;
    private $shirtSizeTransformer;
    private $gameOfficialDetailsFinder;

    public function __construct(
        AssignWorkflow            $assignWorkflow,
        GameOfficialDetailsFinder $gameOfficialDetailsFinder,
        PhoneTransformer          $phoneTransformer,
        ShirtSizeTransformer      $shirtSizeTransformer
    ) {
        $this->assignWorkflow            = $assignWorkflow;
        $this->phoneTransformer          = $phoneTransformer;
        $this->shirtSizeTransformer      = $shirtSizeTransformer;
        $this->gameOfficialDetailsFinder = $gameOfficialDetailsFinder;
    }

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

        // Alignments
        $ws->getStyle($this->colGameNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($this->colDate      )->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($this->colTime . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $ws->getStyle($this->colAge       )->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Titles
        $ws->getCell($this->colGameNumber     . '1')->setValue('Game');
        $ws->getCell($this->colDate           . '1')->setValue('Date');
        $ws->getCell($this->colTime           . '1')->setValue('Time');
        $ws->getCell($this->colFieldName      . '1')->setValue('Field');
        $ws->getCell($this->colHomeTeamPoolId . '1')->setValue('Home Team Pool');
        $ws->getCell($this->colHomeTeamName   . '1')->setValue('Home Team Name');
        $ws->getCell($this->colAwayTeamName   . '1')->setValue('Away Team Name');
        $ws->getCell($this->colAwayTeamPoolId . '1')->setValue('Away Team Pool');

        $ws->getCell($this->colSlot    . '1')->setValue('Slot');
        $ws->getCell($this->colState   . '1')->setValue('State');
        $ws->getCell($this->colName    . '1')->setValue('Official Name');
        $ws->getCell($this->colOrgView . '1')->setValue('SARS');
        $ws->getCell($this->colBadge   . '1')->setValue('Badge');
        $ws->getCell($this->colAge     . '1')->setValue('Age');
        $ws->getCell($this->colShirt   . '1')->setValue('SS');
        $ws->getCell($this->colPhone   . '1')->setValue('Phone');
        $ws->getCell($this->colEmail   . '1')->setValue('Email');

        // Widths
        $ws->getColumnDimension($this->colGameNumber    )->setWidth( 8);
        $ws->getColumnDimension($this->colDate          )->setWidth( 6);
        $ws->getColumnDimension($this->colTime          )->setWidth(10);
        $ws->getColumnDimension($this->colFieldName     )->setWidth(10);
        $ws->getColumnDimension($this->colHomeTeamPoolId)->setWidth(20);
        $ws->getColumnDimension($this->colHomeTeamName  )->setWidth(30);
        $ws->getColumnDimension($this->colAwayTeamName  )->setWidth(30);
        $ws->getColumnDimension($this->colAwayTeamPoolId)->setWidth(20);

        $ws->getColumnDimension($this->colSlot   )->setWidth( 5);
        $ws->getColumnDimension($this->colState  )->setWidth( 6);
        $ws->getColumnDimension($this->colName   )->setWidth(24);
        $ws->getColumnDimension($this->colOrgView)->setWidth(14);
        $ws->getColumnDimension($this->colBadge  )->setWidth( 6);
        $ws->getColumnDimension($this->colAge    )->setWidth( 4);
        $ws->getColumnDimension($this->colShirt  )->setWidth( 6);
        $ws->getColumnDimension($this->colPhone  )->setWidth(20);
        $ws->getColumnDimension($this->colEmail  )->setWidth(30);

        // Special formats for date/time
        $rowCount = (count($games) * 4) + 1;
        
        $startDateFormatCode = 'ddd';
        $cols = sprintf('%s2:%s%d',$this->colDate,$this->colDate,$rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startDateFormatCode);
        
        $startTimeFormatCode = '[$-409]h:mm AM/PM;@';
        $cols = sprintf('%s2:%s%d',$this->colTime,$this->colTime,$rowCount);
        $ws->getStyle($cols)->getNumberFormat()->setFormatCode($startTimeFormatCode);

        $row = 2;
        foreach($games as $game) {
            foreach($game->getOfficials() as $gameOfficial) {
                $this->writeGame($ws,$row,$game,$gameOfficial);
                $row++;
            }
            $row++;
       }
    }
    private function writeGame(\PHPExcel_Worksheet $ws, $row, ScheduleGame $game, ScheduleGameOfficial $gameOfficial)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        $ws->getCell($this->colGameNumber . $row)->setValueExplicit($game->gameNumber);

        // Copied from advanced binder
        $startDate = substr($game->start,0,10);
        $startDateValue = \PHPExcel_Shared_Date::stringToExcel($startDate);
        $ws->getCell($this->colDate . $row)->setValueExplicit($startDateValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        // No built in conversion for time
        $startTime = substr($game->start,10);
        list($h, $m, $s) = explode(':', $startTime);
        $startTimeValue = $h / 24 + $m / 1440 + $s / 86400;
        $ws->getCell($this->colTime . $row)->setValueExplicit($startTimeValue, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $ws->getCell($this->colFieldName      . $row)->setValue($game->fieldName);
        $ws->getCell($this->colHomeTeamPoolId . $row)->setValue($homeTeam->poolTeamView);
        $ws->getCell($this->colHomeTeamName   . $row)->setValue($homeTeam->regTeamName);
        $ws->getCell($this->colAwayTeamName   . $row)->setValue($awayTeam->regTeamName);
        $ws->getCell($this->colAwayTeamPoolId . $row)->setValue($awayTeam->poolTeamView);

        $assignStateView =  $this->assignWorkflow->assignStateAbbreviations[$gameOfficial->assignState];

        $ws->getCell($this->colSlot  . $row)->setValue($gameOfficial->slotView);
        $ws->getCell($this->colState . $row)->setValue($assignStateView);

        if (!$gameOfficial->regPersonName) {
            return;
        }
        $ws->getCell($this->colName  . $row)->setValue($gameOfficial->regPersonName);

        $details = $this->gameOfficialDetailsFinder->findGameOfficialDetails($gameOfficial->regPersonId);
        if (!$details) {
            return;
        }
        $ws->getCell($this->colOrgView  . $row)->setValue($details['orgView']);
        $ws->getCell($this->colBadge    . $row)->setValue(substr($details['refereeBadge'],0,3));
        $ws->getCell($this->colShirt    . $row)->setValue($this->shirtSizeTransformer->transform($details['shirtSize']));
        $ws->getCell($this->colAge      . $row)->setValue($details['age']);
        $ws->getCell($this->colPhone    . $row)->setValue($this->phoneTransformer->transform($details['phone']));
        $ws->getCell($this->colEmail    . $row)->setValue($details['email']);
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