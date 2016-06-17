<?php

namespace AppBundle\Action\Game\Import;

use AppBundle\Common\ExcelReaderTrait;

class GameImportReaderExcel
{
    use ExcelReaderTrait;

    private $games = [];

    protected function processRow($row)
    {
        $colProjectId       = 0;
        $colGameNumber      = 1;
        $colDate            = 2;
        $colTime            = 3;
        $colFieldName       = 4;
        $colHomeTeamPoolKey = 5;
        $colHomeTeamName    = 6;
        $colAwayTeamName    = 7;
        $colAwayTeamPoolKey = 8;

        // Skip empty lines
        $projectId = trim($row[$colProjectId]);
        if (!$projectId) return;
        
        $gameNumber = (integer)trim($row[$colGameNumber]);
        if (!$gameNumber) return null;

        $date = $this->processDate($row[$colDate]);
        $time = $this->processTime($row[$colTime]);

        $game = [
            'projectId'       => $projectId,
            'gameNumber'      => $gameNumber,
            'date'            => $date,
            'time'            => $time,
            'fieldName'       => trim($row[$colFieldName]),
            'homeTeamPoolKey' => trim($row[$colHomeTeamPoolKey]),
            'awayTeamPoolKey' => trim($row[$colAwayTeamPoolKey]),
            'homeTeamName'    => trim($row[$colHomeTeamName]),
            'awayTeamName'    => trim($row[$colAwayTeamName]),
        ];
        $this->games[] = $game;
    }
    public function read($filename)
    {
        // Tosses exception
        $reader = \PHPExcel_IOFactory::createReaderForFile($filename);
        
        // Need this otherwise dates and such are returned formatted
        /** @noinspection PhpUndefinedMethodInspection */
        $reader->setReadDataOnly(true);

        // Just grab all the rows
        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);
        $rows = $ws->toArray();
        array_shift($rows); // Discard header line
    
        foreach($rows as $row) {
            $this->processRow($row);
        }
        return $this->games;
    }
}