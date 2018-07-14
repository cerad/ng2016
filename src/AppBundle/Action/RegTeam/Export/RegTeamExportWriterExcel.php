<?php
namespace AppBundle\Action\RegTeam\Export;

use AppBundle\Action\Game\RegTeam;

class RegTeamExportWriterExcel
{
    private $wb;

    /**
     * @param  RegTeam[] regTeams
     * @param  string filename
     * @return string
     * @throws \PHPExcel_Exception
     */
    public function write(array $regTeams, $filename='php://output')
    {
        // Not sure this is needed
        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());

        $this->wb = $wb = new \PHPExcel();

        $ws = $wb->getSheet();

        $this->writeRegTeams($ws, $regTeams);

        return $this->getContents($filename);
    }
    private $colProjectId    = 'A';
    private $colRegTeamKey   = 'B';
    private $colRegTeamName  = 'C';
    private $colSars         = 'D';
    private $colRegion       = 'E';
    private $colPoints       = 'F';

    private $colPoolTeamKey0 = 'G';
    private $colPoolTeamKey1 = 'H';
    private $colPoolTeamKey2 = 'I';
    private $colPoolTeamKey3 = 'J';

    /**
     * @param  \PHPExcel_Worksheet $ws
     * @param   RegTeam[] $regTeams
     * @throws \PHPExcel_Exception
     */
    private function writeRegTeams(\PHPExcel_Worksheet $ws,$regTeams)
    {
        $ws->setTitle('RegTeams');

        $ws->getCell($this->colProjectId   . '1')->setValue('ProjectId');
        $ws->getCell($this->colRegTeamKey  . '1')->setValue('Team Key');
        $ws->getCell($this->colRegTeamName . '1')->setValue('Team Name');
        $ws->getCell($this->colSars        . '1')->setValue('SARS');
        $ws->getCell($this->colRegion      . '1')->setValue('Region');
        $ws->getCell($this->colPoints      . '1')->setValue('SF Pts');

        $ws->getCell($this->colPoolTeamKey0 . '1')->setValue('PP Team Key');
        $ws->getCell($this->colPoolTeamKey1 . '1')->setValue('QF Team Key');
        $ws->getCell($this->colPoolTeamKey2 . '1')->setValue('SF Team Key');
        $ws->getCell($this->colPoolTeamKey3 . '1')->setValue('CO/TF Team Key');

        $ws->getColumnDimension($this->colProjectId  )->setWidth(24);
        $ws->getColumnDimension($this->colRegTeamKey )->setWidth(16);
        $ws->getColumnDimension($this->colRegTeamName)->setWidth(32);
        $ws->getColumnDimension($this->colSars       )->setWidth(16);
        $ws->getColumnDimension($this->colRegion     )->setWidth(12);
        $ws->getColumnDimension($this->colPoints     )->setWidth( 8);

        $ws->getColumnDimension($this->colPoolTeamKey0)->setWidth(16);
        $ws->getColumnDimension($this->colPoolTeamKey1)->setWidth(16);

        $ws->getColumnDimension($this->colPoolTeamKey2)->setWidth(16);
        $ws->getColumnDimension($this->colPoolTeamKey3)->setWidth(16);

        $ws->getStyle($this->colPoints)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach($regTeams as $regTeam) {

            $ws->getCell($this->colProjectId   . $row)->setValue($regTeam->projectId);
            $ws->getCell($this->colRegTeamKey  . $row)->setValue($regTeam->teamKey);
            $ws->getCell($this->colRegTeamName . $row)->setValue($regTeam->teamName);
            $ws->getCell($this->colSars        . $row)->setValue($regTeam->orgView);
            $ws->getCell($this->colRegion      . $row)->setValue($regTeam->orgId);

            foreach($regTeam->poolTeams as $poolTeam) {
                switch($poolTeam->poolTypeKey) {
                    
                    case 'PP' :
                        $ws->getCell($this->colPoolTeamKey0 . $row)->setValue($poolTeam->poolTeamKey);
                        if ($poolTeam->regTeamPoints !== null) {
                            $ws->getCell($this->colPoints . $row)->setValue($poolTeam->regTeamPoints);
                        }
                        break;

                    case 'QF' : $ws->getCell($this->colPoolTeamKey1 . $row)->setValue($poolTeam->poolTeamKey); break;
                    case 'SF' : $ws->getCell($this->colPoolTeamKey2 . $row)->setValue($poolTeam->poolTeamKey); break;
                    case 'CO':
                    case 'TF' : $ws->getCell($this->colPoolTeamKey3 . $row)->setValue($poolTeam->poolTeamKey); break;
                }
            }
            $row++;
        }
    }
    private function getContents($filename)
    {
        $writer = \PHPExcel_IOFactory::createWriter($this->wb, "Excel2007");
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