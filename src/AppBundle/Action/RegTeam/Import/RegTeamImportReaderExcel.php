<?php

namespace AppBundle\Action\RegTeam\Import;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Common\ExcelReaderTrait;

class RegTeamImportReaderExcel
{
    use ExcelReaderTrait;

    private $regTeams = [];

    private $regionFinder;

    public function __construct(
        PhysicalAysoRepository $regionFinder
    )
    {
        $this->regionFinder = $regionFinder;
    }

    private function processRow($row)
    {
        $colProjectId    = 0;
        $colTeamKey      = 1;
        $colTeamName     = 2;
        $colOrgView      = 3;
        $colRegion       = 4;
        $colPoints       = 5;
        $colPoolTeamKey0 = 6;
        $colPoolTeamKey1 = 7;
        $colPoolTeamKey2 = 8;
        $colPoolTeamKey3 = 9;

        $regTeamKey  = trim($row[$colTeamKey]);
        $regTeamName = trim(($row[$colTeamName]));
        if (!$regTeamKey) return;
        
        $projectId = trim($row[$colProjectId]);
        $regTeamId = strpos($regTeamKey, 'DELETE ') === 0 ?
            $projectId . ':' . substr($regTeamKey,7) :
            $projectId . ':' . $regTeamKey;

        $region = (integer)trim($row[$colRegion]);
        $orgId  = $region ? sprintf('AYSOR:%04u',$region) : trim($row[$colRegion]);
        $orgView = trim($row[$colOrgView]);

        if ($orgId) {
            if (!$orgView || strpos($regTeamName,'SARS') !== false) {

                $org = $this->regionFinder->findOrg($orgId);
                if ($org) {
                    $sarParts = explode('/', $org['sar']);
                    $sars = sprintf('%02u-%s-%04u-%s', $sarParts[0], $sarParts[1], $sarParts[2], $org['state']);
                    $orgView = $orgView ? : $sars;
                    $regTeamName = str_replace('SARS',$sars,$regTeamName);
                }
            }
        }
        $regTeam = [
            'projectId'      => $projectId,
            'regTeamId'      => $regTeamId,
            'regTeamKey'     => $regTeamKey,
            'regTeamName'    => $regTeamName,
            'orgId'          => $orgId,
            'orgView'        => $orgView,
            'regionNumber'   => $region,
            'points'         => (integer)trim($row[$colPoints]),
            'poolTeamKeys'   => [
                trim($row[$colPoolTeamKey0]),
                trim($row[$colPoolTeamKey1]),
                trim($row[$colPoolTeamKey2]),
                trim($row[$colPoolTeamKey3]),
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
        $ws = $sheet ? $wb->getSheetByName($sheet) : $wb->getSheet(0);
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