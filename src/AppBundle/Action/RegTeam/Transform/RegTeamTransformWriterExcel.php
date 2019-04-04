<?php
namespace AppBundle\Action\RegTeam\Transform;

use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RegTeamTransformWriterExcel
{
    /** @var Spreadsheet */
    private $wb;

    /**
     * @param array $regTeams
     * @param $sheet
     * @return false|string
     * @throws PhpSpreadsheet\Exception
     */
    public function write(array $regTeams, $sheet)
    {
        // Not sure this is needed
        Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

        $this->wb = new Spreadsheet();

        $ws = $this->wb->getActiveSheet();

        $this->writeRegTeams($ws, $regTeams, $sheet);
        
        return $this->getContents();
    }

    /**
     * @param Worksheet $ws
     * @param array $regTeams
     * @param $sheet
     * @throws PhpSpreadsheet\Exception
     */
    private function writeRegTeams(Worksheet $ws, array $regTeams,$sheet)
    {
        $ws->setTitle($sheet);

        $colRegTeamKey  = 'A';
        $colRegTeamName = 'B';
        $colOrgView     = 'C';
        $colRegion      = 'D';
        $colPoints      = 'E';
        $colPoolTeam0   = 'F';
        $colPoolTeam1   = 'G';
        $colPoolTeam2   = 'H';
        $colPoolTeam3   = 'I';

        $ws->getCell($colRegTeamKey  . '1')->setValue('Team Key');
        $ws->getCell($colRegTeamName . '1')->setValue('Team Name');
        $ws->getCell($colOrgView     . '1')->setValue('S-A-R-St');
        $ws->getCell($colRegion      . '1')->setValue('Region');
        $ws->getCell($colPoints      . '1')->setValue('Soccerfest Points');
        $ws->getCell($colPoolTeam0   . '1')->setValue('Pool Team Key');
        $ws->getCell($colPoolTeam1   . '1')->setValue('QF Pool Team 1');
        $ws->getCell($colPoolTeam2   . '1')->setValue('SF Pool Team 2');
        $ws->getCell($colPoolTeam3   . '1')->setValue('FM Pool Team 3');

        $ws->getColumnDimension($colRegTeamKey )->setWidth(16);
        $ws->getColumnDimension($colRegTeamName)->setWidth(32);
        $ws->getColumnDimension($colOrgView    )->setWidth(14);
        $ws->getColumnDimension($colRegion     )->setWidth( 8);
        $ws->getColumnDimension($colPoints     )->setWidth(16);
        $ws->getColumnDimension($colPoolTeam0  )->setWidth(16);
        $ws->getColumnDimension($colPoolTeam1  )->setWidth(16);
        $ws->getColumnDimension($colPoolTeam2  )->setWidth(16);
        $ws->getColumnDimension($colPoolTeam3  )->setWidth(16);

        $ws->getStyle($colRegion)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach($regTeams as $regTeam) {

            $ws->getCell($colRegTeamKey  . $row)->setValue($regTeam['regTeamKey']);
            $ws->getCell($colRegTeamName . $row)->setValue($regTeam['regTeamName']);
            $ws->getCell($colOrgView     . $row)->setValue($regTeam['orgView']);
            $ws->getCell($colRegion      . $row)->setValue($regTeam['regionNumber']);
            $ws->getCell($colPoints      . $row)->setValue(null);
            $ws->getCell($colPoolTeam0   . $row)->setValue(null);
            $ws->getCell($colPoolTeam1   . $row)->setValue(null);
            $ws->getCell($colPoolTeam2   . $row)->setValue(null);
            $ws->getCell($colPoolTeam3   . $row)->setValue(null);
            $row++;
        }
    }

    /**
     * @return false|string
     * @throws PhpSpreadsheet\Writer\Exception
     */
    private function getContents()
    {
        $writer = IOFactory::createWriter($this->wb, "Xlsx");
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return 'xlsx';
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

}