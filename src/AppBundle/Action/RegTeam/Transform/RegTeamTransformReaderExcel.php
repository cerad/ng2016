<?php

namespace AppBundle\Action\RegTeam\Transform;

use Cerad\Bundle\AysoBundle\AysoFinder;
use AppBundle\Common\ExcelReaderTrait;

class RegTeamTransformReaderExcel
{
    use ExcelReaderTrait;

    private $regTeams = [];

    private $regionFinder;

    public function __construct(
        AysoFinder $regionFinder
    )
    {
        $this->regionFinder = $regionFinder;
    }
    protected function processRow($row,$div)
    {
        $colTeamNumber     =  9; // J
        $colSar            = 10;
        $colCoachNameFirst = 11;
        $colCoachNameLast  = 12;

        $teamNumber = (integer)trim(($row[$colTeamNumber]));
        if (!$teamNumber) {
            return;
        }

        // Just make the sar stuff work for now
        $sar = trim($row[$colSar]);

        $sarParts = explode('-',$sar);
        if (count($sarParts) !== 3) {
            return;
        }
        $regionNumber = (integer)$sarParts[2];
        $orgKey = sprintf('AYSOR:%04u',$regionNumber);
        $org = $this->regionFinder->findOrg($orgKey);

        $sarParts = explode('/',$org['sar']);
        $orgView = sprintf('%02u-%s-%04u-%s',$sarParts[0],$sarParts[1],$sarParts[2],$org['state']);

        $coachNameLast  = trim($row[$colCoachNameLast]);
        $coachNameFirst = trim($row[$colCoachNameFirst]);

        $regTeam = [
            'regTeamKey'     => sprintf('%sCore%02u',$div,$teamNumber),
            'regTeamName'    => sprintf('#%02u %s %s',$teamNumber,$orgView,$coachNameLast),
            'regTeamNumber'  => $teamNumber,
            'regionNumber'   => $regionNumber,
            'orgKey'         => $orgKey,
            'orgView'        => $orgView,
            'coachFirstName' => $coachNameFirst,
            'coachLastName'  => $coachNameLast,
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
        foreach($rows as $row) {
            $this->processRow($row,$sheet); // U10G etc
        }
        return $this->regTeams;
    }
}