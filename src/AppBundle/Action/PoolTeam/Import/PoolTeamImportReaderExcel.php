<?php

namespace AppBundle\Action\PoolTeam\Import;

use AppBundle\Common\ExcelReaderTrait;
use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PoolTeamImportReaderExcel
{
    use ExcelReaderTrait;

    /**
     * @var array
     */
    private $poolTeams = [];

    /**
     * @param $row1
     * @param $row2
     */
    protected function processRow($row1,$row2)
    {
        $colProjectId     =  0;
        $colPoolKey       =  1;
        $colPoolSlot      =  2;
        $colPoolTypeKey   =  3;
        $colPoolTeamKey   =  4;
        $colPoolTeamSlot  =  5;
        $colRegTeamKey    =  6;
        $colRegTeamPoints =  7;
        $colProgram       =  8;
        $colGender        =  9;
        $colAge           = 10;
        $colDivision      = 11;

        $projectId   = trim($row1[$colProjectId]);
        $poolTeamKey = trim($row1[$colPoolTeamKey]);
        
        if ($poolTeamKey[0] === '~') {
            $poolTeamKey = substr($poolTeamKey,1);
            $poolTeamDelete = true;
        }
        else $poolTeamDelete = false;

        $poolTeamId = $projectId . ':' . $poolTeamKey;
        
        $poolTeam = [
            'poolTeamId'  => $poolTeamId,
            'projectId'   => $projectId,
            'poolKey'     => trim($row1[$colPoolKey]),
            'poolTypeKey' => trim($row1[$colPoolTypeKey]),
            'poolTeamKey' => $poolTeamKey,
            
            'poolTeamDelete' => $poolTeamDelete,
            
            'poolView'     => trim($row2[$colPoolKey]),
            'poolSlotView' => $row2[$colPoolSlot], // Bit of a hack but allow leading spaces here

            'poolTypeView'     => trim($row2[$colPoolTypeKey]),
            'poolTeamView'     => trim($row2[$colPoolTeamKey]),
            'poolTeamSlotView' => $row2[$colPoolTeamSlot],

            'program'  => trim($row1[$colProgram]),
            'gender'   => trim($row1[$colGender]),
            'age'      => trim($row1[$colAge]),
            'division' => trim($row1[$colDivision]),

            'regTeamId'     => $projectId . ':' . trim($row1[$colRegTeamKey]),
            'regTeamName'   => trim($row2[$colRegTeamKey]),
            'regTeamPoints' => (integer)trim($row1[$colRegTeamPoints]),

        ];
        $this->poolTeams[] = $poolTeam;
    }

    /**
     * @param $filename
     * @return array
     * @throws PhpSpreadsheet\Exception
     * @throws PhpSpreadsheet\Reader\Exception
     */
    public function read($filename)
    {
        // Tosses exception
        $reader = IOFactory::createReaderForFile($filename);
        
        // Need this otherwise dates and such are returned formatted
        /** @noinspection PhpUndefinedMethodInspection */
        $reader->setReadDataOnly(true);

        // Just grab all the rows
        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);
        $rows = $ws->toArray();
        array_shift($rows); // Discard header line

        // Process in pairs
        $rowCount = count($rows);
        $rowIndex = 0;
        while($rowIndex < $rowCount) {
            $row1 = $rows[$rowIndex++];
            if (trim($row1[0])) { // Skip empty lines
                $row2 = $rows[$rowIndex++];
                $this->processRow($row1,$row2);
            }
        }
        return $this->poolTeams;
    }
}