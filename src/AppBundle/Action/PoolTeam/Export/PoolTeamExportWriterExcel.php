<?php
namespace AppBundle\Action\PoolTeam\Export;

use AppBundle\Action\Game\PoolTeam;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer;
use PhpOffice\PhpSpreadsheet\Exception;

class PoolTeamExportWriterExcel
{
    private $ws;

    /**
     * @param  PoolTeam[] $poolTeams
     * @return string
     * @throws Exception
     */
    public function write(array $poolTeams)
    {
        // Not sure this is needed
        Cell::setValueBinder(new AdvancedValueBinder());

        $this->ws = new Spreadsheet();
        $ws = $this->ws->getActiveSheet();

        $this->writePoolTeams($ws, $poolTeams);
        
        return $this->getContents();
    }

    /**
     * @param Worksheet $ws
     * @param PoolTeam[] $poolTeams
     * @throws Exception
     */
    private function writePoolTeams(Worksheet $ws,$poolTeams)
    {

        $ws->setTitle('PoolTeams');

        $colProjectId    = 'A';
        $colPoolKey      = 'B';
        $colPoolSlot     = 'C';
        $colPoolTypeKey  = 'D';
        $colPoolTeamKey  = 'E';
        $colPoolTeamSlot = 'F';

        $colRegTeamKey    = 'G';
        $colRegTeamPoints = 'H';

        $colProgram  = 'I';
        $colGender   = 'J';
        $colAge      = 'K';
        $colDivision = 'L';

        // Not really sure about this ABC stuff but try for now
        $ws->getCell($colProjectId    . '1')->setValue('ProjectId');
        $ws->getCell($colPoolKey      . '1')->setValue('PoolKey');
        $ws->getCell($colPoolSlot     . '1')->setValue('PSlot');
        $ws->getCell($colPoolTypeKey  . '1')->setValue('PType');
        $ws->getCell($colPoolTeamKey  . '1')->setValue('PoolTeamKey');
        $ws->getCell($colPoolTeamSlot . '1')->setValue('TSlot');

        $ws->getCell($colRegTeamKey    . '1')->setValue('Reg Team');
        $ws->getCell($colRegTeamPoints . '1')->setValue('PTS');

        $ws->getCell($colProgram  . '1')->setValue('Prog');
        $ws->getCell($colGender   . '1')->setValue('Gen');
        $ws->getCell($colAge      . '1')->setValue('Age');
        $ws->getCell($colDivision . '1')->setValue('Div');

        $ws->getColumnDimension($colProjectId   )->setWidth(24);
        $ws->getColumnDimension($colPoolKey     )->setWidth(24);
        $ws->getColumnDimension($colPoolSlot    )->setWidth( 8);
        $ws->getColumnDimension($colPoolTypeKey )->setWidth(10);
        $ws->getColumnDimension($colPoolTeamKey )->setWidth(24);
        $ws->getColumnDimension($colPoolTeamSlot)->setWidth(10);

        $ws->getColumnDimension($colRegTeamKey)   ->setWidth(20);
        $ws->getColumnDimension($colRegTeamPoints)->setWidth( 4);

        $ws->getColumnDimension($colProgram )->setWidth(8);
        $ws->getColumnDimension($colGender  )->setWidth(6);
        $ws->getColumnDimension($colAge     )->setWidth(6);
        $ws->getColumnDimension($colDivision)->setWidth(6);

        $row = 2;
        foreach($poolTeams as $poolTeam) {

            $ws->getCell($colProjectId   . $row)->setValue($poolTeam->projectId);
            $ws->getCell($colPoolKey     . $row)->setValue($poolTeam->poolKey);
            $ws->getCell($colPoolTypeKey . $row)->setValue($poolTeam->poolTypeKey);
            $ws->getCell($colPoolTeamKey . $row)->setValue($poolTeam->poolTeamKey);

            $regTeamIdParts = explode(':',$poolTeam->regTeamId);
            $regTeamKey = count($regTeamIdParts) === 2 ? $regTeamIdParts[1] : null;
            $ws->getCell($colRegTeamKey    . $row)->setValue($regTeamKey);
            $ws->getCell($colRegTeamPoints . $row)->setValue($poolTeam->regTeamPoints);

            $ws->getCell($colProgram  . $row)->setValue($poolTeam->program);
            $ws->getCell($colGender   . $row)->setValue($poolTeam->gender);
            $ws->getCell($colAge      . $row)->setValue($poolTeam->age);
            $ws->getCell($colDivision . $row)->setValue($poolTeam->division);

            $row++;

            $ws->getCell($colProjectId    . $row)->setValue('Views');
            $ws->getCell($colPoolKey      . $row)->setValue($poolTeam->poolView);
            $ws->getCell($colPoolSlot     . $row)->setValueExplicit($poolTeam->poolSlotView,DataType::TYPE_STRING);
            $ws->getCell($colPoolTypeKey  . $row)->setValue($poolTeam->poolTypeView);
            $ws->getCell($colPoolTeamKey  . $row)->setValue($poolTeam->poolTeamView);
            $ws->getCell($colPoolTeamSlot . $row)->setValueExplicit($poolTeam->poolTeamSlotView,DataType::TYPE_STRING);

            $ws->getCell($colRegTeamKey   . $row)->setValue($poolTeam->regTeamName);

            $row += 2;
        }
    }

    /**
     * @return false|string
     * @throws Writer\Exception
     */
    private function getContents()
    {
        $writer = IOFactory::createWriter($this->ws, "Xlsx");
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