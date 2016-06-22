<?php

namespace AppBundle\Action\RegTeam\Import;

use AppBundle\Common\ExcelReaderTrait;

class RegTeamImportReaderExcel
{
    use ExcelReaderTrait;

    private $regTeams = [];

    private function processRow($row)
    {
        $colTeamKey      = 0;
        $colTeamName     = 1;
        $colOrgView      = 2;
        $colRegion       = 3;
        $colPoints       = 4;
        $colPoolTeamKey0 = 5;
        $colPoolTeamKey1 = 6;
        $colPoolTeamKey2 = 7;
        $colPoolTeamKey3 = 8;

        $regTeamKey = trim(($row[$colTeamKey]));
        if (!$regTeamKey) return;

        $region = (integer)trim(($row[$colRegion]));
        $orgId  = sprintf('AYSOR:%04u',$region);

        $regTeam = [
            'regTeamKey'     => $regTeamKey,
            'regTeamName'    => trim(($row[$colTeamName])),
            'orgId'          => $orgId,
            'orgView'        => trim(($row[$colOrgView])),
            'region'         => $region,
            'points'         => (integer)trim(($row[$colPoints])),
            'poolTeamKeys'   => [
                trim(($row[$colPoolTeamKey0])),
                trim(($row[$colPoolTeamKey1])),
                trim(($row[$colPoolTeamKey2])),
                trim(($row[$colPoolTeamKey3])),
            ],
        ];
        $this->regTeams[] = $regTeam;
    }
    public function read($filename,$sheet)
    {
        // Tosses exception
        $reader = \PHPExcel_IOFactory::createReaderForFile($filename);
        
        // Need this otherwise dates and such are returned formatted
        /** @noinspection PhpUndefinedMethodInspection */
        $reader->setReadDataOnly(true);

        // Just grab all the rows
        $wb = $reader->load($filename);
        $ws = $wb->getSheetByName($sheet);
        $rows = $ws->toArray();
        array_shift($rows); // Discard header line

        // Process in pairs
        $this->regTeams = [];
        foreach($rows as $row) {
            $this->processRow($row);
        }
        return $this->regTeams;
    }
}