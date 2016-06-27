<?php
namespace AppBundle\Action\GameOfficial\Summary;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\RegPerson\RegPerson;

use AppBundle\Common\ExcelWriterTrait;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\ShirtSizeTransformer;

class SummaryWriterExcel
{
    use ExcelWriterTrait;

    private $phoneTransformer;
    private $shirtSizeTransformer;
    private $regionToSarTransformer;

    public function __construct(
        RegionToSarTransformer $regionToSarTransformer,
        PhoneTransformer       $phoneTransformer,
        ShirtSizeTransformer   $shirtSizeTransformer
    ) {
        $this->phoneTransformer       = $phoneTransformer;
        $this->shirtSizeTransformer   = $shirtSizeTransformer;
        $this->regionToSarTransformer = $regionToSarTransformer;
    }
    /**
     * @param  RegPerson[] $regPersons
     * @param  Game[]      $games
     * @return string
     * @throws \PHPExcel_Exception
     */
    public function write(array $regPersons, array $games)
    {
        $wb = $this->createWorkBook();

        // Only referees
        $regPersons = array_filter($regPersons,function(RegPerson $regPerson)
        {
            if ($regPerson->isReferee) return true;

            return false;
        });
        usort($regPersons,function(RegPerson $regPerson1, RegPerson $regPerson2)
        {
            return strcmp(strtolower($regPerson1->name),strtolower($regPerson2->name));
        });
        usort($games,function(Game $game1, Game $game2)
        {
            if ($game1->start < $game2->start) return -1;
            if ($game1->start > $game2->start) return +1;
        });
        $gameOfficialsMap = $this->generateGameOfficialsMap($games);

        $ws = $wb->createSheet(0);
        $this->writeSummary($ws, $regPersons,$gameOfficialsMap);

        $ws = $wb->createSheet(1);
        $this->writeGames($ws, $regPersons,$gameOfficialsMap);

        $wb->setActiveSheetIndex(1);
        return $this->getContents();
    }

    /**
     * @param  Game[] $games
     * @return array
     */
    private function generateGameOfficialsMap($games)
    {
        $gameOfficialsMap = [];
        foreach($games as $game) {
            foreach($game->getOfficials() as $gameOfficial) {
                if ($gameOfficial->phyPersonId) {
                    $gameOfficial->game = $game;
                    $gameOfficialsMap[$gameOfficial->phyPersonId][] = $gameOfficial;
                }
            }
        }
        return $gameOfficialsMap;
    }
    /** =========================================
     * @param  \PHPExcel_Worksheet $ws
     * @param   RegPerson[]  $regPersons
     * @param   array        $gameOfficialsMap
     * @throws \PHPExcel_Exception
     */
    private function writeGames(\PHPExcel_Worksheet $ws,$regPersons,$gameOfficialsMap)
    {
        $ws->setTitle('Referee Games');

        $col = 'A';
        $colOfficialName  = $col++;
        $colOfficialBadge = $col++;
        $colOfficialSars  = $col++;
        $colOfficialAge   = $col++;
        $colOfficialSlot  = $col++;

        $colGameNumber = $col++;
        $colGameDate   = $col++;
        $colGameTime   = $col++;
        $colFieldName  = $col++;

        $colHomeTeamPoolKey = $col++;
        $colHomeTeamName    = $col++;
        $colAwayTeamName    = $col++;
        $colAwayTeamPoolKey = $col;

        $this->setColAlignCenter($ws,$colGameNumber);
        $this->setColAlignCenter($ws,$colGameDate);
        $this->setColAlignCenter($ws,$colGameTime);
        $this->setColAlignCenter($ws,$colOfficialAge);

        $this->setColWidth($ws,$colOfficialName,   24);
        $this->setColWidth($ws,$colOfficialBadge,   6);
        $this->setColWidth($ws,$colOfficialSars,   16);
        $this->setColWidth($ws,$colOfficialAge,     5);
        $this->setColWidth($ws,$colOfficialSlot,    6);
        $this->setColWidth($ws,$colGameNumber,      8);
        $this->setColWidth($ws,$colGameDate,        6);
        $this->setColWidth($ws,$colGameTime,       10);
        $this->setColWidth($ws,$colFieldName,      10);
        $this->setColWidth($ws,$colHomeTeamPoolKey,20);
        $this->setColWidth($ws,$colHomeTeamName,   30);
        $this->setColWidth($ws,$colAwayTeamName,   30);
        $this->setColWidth($ws,$colAwayTeamPoolKey,20);

        $row = 1;
        $this->setCellValue($ws,$colOfficialName,   $row,'Referee Name');
        $this->setCellValue($ws,$colOfficialBadge,  $row,'Badge');
        $this->setCellValue($ws,$colOfficialSars,   $row,'SARS');
        $this->setCellValue($ws,$colOfficialAge,    $row,'Age' );
        $this->setCellValue($ws,$colOfficialSlot,   $row,'Slot');
        $this->setCellValue($ws,$colGameNumber,     $row,'Game');
        $this->setCellValue($ws,$colGameDate,       $row,'Date');
        $this->setCellValue($ws,$colGameTime,       $row,'Time');
        $this->setCellValue($ws,$colFieldName,      $row,'Field');
        $this->setCellValue($ws,$colHomeTeamPoolKey,$row,'Home Team Pool');
        $this->setCellValue($ws,$colHomeTeamName,   $row,'Home Team Name');
        $this->setCellValue($ws,$colAwayTeamName,   $row,'Away Team Name');
        $this->setCellValue($ws,$colAwayTeamPoolKey,$row,'Away Team Pool');
        $ws->freezePane('A2');

        $row = 2;
        foreach($regPersons as $regPerson) {
            if (isset($gameOfficialsMap[$regPerson->personId])) {
                $row++;
                /** @var GameOfficial $gameOfficial */
                foreach($gameOfficialsMap[$regPerson->personId] as $gameOfficial)
                {
                    $this->setCellValue($ws,$colOfficialName, $row,$regPerson->name);
                    $this->setCellValue($ws,$colOfficialBadge,$row,substr($regPerson->refereeBadge,0,3));

                    $orgView = $this->regionToSarTransformer->transform(($regPerson->orgId));
                    $this->setCellValue($ws,$colOfficialSars,$row,$orgView);

                    $this->setCellValue($ws,$colOfficialAge, $row,$regPerson->age);
                    $this->setCellValue($ws,$colOfficialSlot,$row,$gameOfficial->slotView);

                    $game = $gameOfficial->game;
                    $this->setCellValue($ws,$colGameNumber,$row,$game->gameNumber);
                    $this->setCellValue($ws,$colFieldName, $row,$game->fieldName);

                    $this->setCellValueDate($ws,$colGameDate,$row,$game->start);
                    $this->setCellValueTime($ws,$colGameTime,$row,$game->start);

                    $homeTeam = $game->homeTeam;
                    $awayTeam = $game->awayTeam;

                    $this->setCellValue($ws,$colHomeTeamPoolKey,$row,$homeTeam->poolTeamKey);
                    $this->setCellValue($ws,$colHomeTeamName,   $row,$homeTeam->regTeamName);

                    $this->setCellValue($ws,$colAwayTeamPoolKey,$row,$awayTeam->poolTeamKey);
                    $this->setCellValue($ws,$colAwayTeamName,   $row,$awayTeam->regTeamName);

                    $row++;
                }
            }
        }
    }
    /** =========================================
     * @param  \PHPExcel_Worksheet $ws
     * @param   RegPerson[]  $regPersons
     * @param   array        $gameOfficialsMap
     * @throws \PHPExcel_Exception
     */
    private function writeSummary(\PHPExcel_Worksheet $ws,$regPersons,$gameOfficialsMap)
    {
        $ws->setTitle('Referee Summary');

        $col = 'A';
        $colRegPersonName = $col++;

        $colStatSlotAll  = $col++;
        $colStatSlotRef  = $col++;
        $colStatSlotAr   = $col++;
        $colStatSlotYc   = $col++;
        $colStatSlotRc   = $col++;
        $colSkip1        = $col++;
        $colStatSlotWed  = $col++;
        $colStatSlotThu  = $col++;
        $colStatSlotFri  = $col++;
        $colStatSlotSat  = $col++;
        $colStatSlotSun  = $col++;
        $colSkip2        = $col++;

        $colBadge = $col++;
        $colSars  = $col++;
        $colAge   = $col++;
        $colEmail = $col++;
        $colPhone = $col++;
        $colShirt = $col;

        $this->setColAlignCenter($ws,$colAge);
        $this->setColAlignCenter($ws,$colBadge);

        $this->setColAlignCenter($ws,$colStatSlotAll);
        $this->setColAlignCenter($ws,$colStatSlotRef);
        $this->setColAlignCenter($ws,$colStatSlotAr);
        $this->setColAlignCenter($ws,$colStatSlotYc);
        $this->setColAlignCenter($ws,$colStatSlotRc);
        $this->setColAlignCenter($ws,$colStatSlotWed);
        $this->setColAlignCenter($ws,$colStatSlotThu);
        $this->setColAlignCenter($ws,$colStatSlotFri);
        $this->setColAlignCenter($ws,$colStatSlotSat);
        $this->setColAlignCenter($ws,$colStatSlotSun);

        $this->setColWidth($ws,$colRegPersonName,24);
        $this->setColWidth($ws,$colBadge,         6);
        $this->setColWidth($ws,$colSars,         16);
        $this->setColWidth($ws,$colAge,           5);
        $this->setColWidth($ws,$colEmail,        32);
        $this->setColWidth($ws,$colPhone,        14);
        $this->setColWidth($ws,$colShirt,         6);

        $this->setColWidth($ws,$colStatSlotAll,   5);
        $this->setColWidth($ws,$colStatSlotRef,   5);
        $this->setColWidth($ws,$colStatSlotAr,    5);
        $this->setColWidth($ws,$colStatSlotYc,    5);
        $this->setColWidth($ws,$colStatSlotRc,    5);
        $this->setColWidth($ws,$colSkip1,         5);
        $this->setColWidth($ws,$colStatSlotWed,   5);
        $this->setColWidth($ws,$colStatSlotThu,   5);
        $this->setColWidth($ws,$colStatSlotFri,   5);
        $this->setColWidth($ws,$colStatSlotSat,   5);
        $this->setColWidth($ws,$colStatSlotSun,   5);
        $this->setColWidth($ws,$colSkip2,         5);

        $row = 1;
        $this->setCellValue($ws,$colRegPersonName,$row,'Name');
        $this->setCellValue($ws,$colBadge,        $row,'Badge');
        $this->setCellValue($ws,$colSars,         $row,'SARS');
        $this->setCellValue($ws,$colAge,          $row,'Age');
        $this->setCellValue($ws,$colEmail,        $row,'Email');
        $this->setCellValue($ws,$colPhone,        $row,'Phone');
        $this->setCellValue($ws,$colShirt,        $row,'Shirt');

        $this->setCellValue($ws,$colStatSlotAll,  $row,'ALL');
        $this->setCellValue($ws,$colStatSlotRef,  $row,'REF');
        $this->setCellValue($ws,$colStatSlotAr,   $row,'AR');
        $this->setCellValue($ws,$colStatSlotYc,   $row,'YC');
        $this->setCellValue($ws,$colStatSlotRc,   $row,'RC');
        $this->setCellValue($ws,$colStatSlotWed,  $row,'WEN');
        $this->setCellValue($ws,$colStatSlotThu,  $row,'THU');
        $this->setCellValue($ws,$colStatSlotFri,  $row,'FRI');
        $this->setCellValue($ws,$colStatSlotSat,  $row,'SAT');
        $this->setCellValue($ws,$colStatSlotSun,  $row,'SUN');

        $ws->freezePane('A2');

        $row = 2;
        foreach($regPersons as $regPerson) {

            $stats = $this->generateStats($regPerson,$gameOfficialsMap);

            $this->setCellValue($ws,$colRegPersonName,$row,$regPerson->name);
            $this->setCellValue($ws,$colBadge,        $row,substr($regPerson->refereeBadge,0,3));

            $this->setCellValueStat($ws,$colStatSlotAll,$row,$stats['slotAll']);
            $this->setCellValueStat($ws,$colStatSlotRef,$row,$stats['slotRef']);
            $this->setCellValueStat($ws,$colStatSlotAr, $row,$stats['slotAr' ]);

            $this->setCellValueStat($ws,$colStatSlotYc,$row,$stats['yc']);
            $this->setCellValueStat($ws,$colStatSlotRc,$row,$stats['rc']);

            $this->setCellValueStat($ws,$colStatSlotWed,$row,$stats['wed']);
            $this->setCellValueStat($ws,$colStatSlotThu,$row,$stats['thu']);
            $this->setCellValueStat($ws,$colStatSlotFri,$row,$stats['fri']);
            $this->setCellValueStat($ws,$colStatSlotSat,$row,$stats['sat']);
            $this->setCellValueStat($ws,$colStatSlotSun,$row,$stats['sun']);

            $orgView = $this->regionToSarTransformer->transform(($regPerson->orgId));
            $this->setCellValue($ws,$colSars,$row,$orgView);

            $this->setCellValue($ws,$colAge,$row,$regPerson->age);
            $this->setCellValue($ws,$colEmail,$row,$regPerson->email);

            $phone = $this->phoneTransformer->transform(($regPerson->phone));
            $this->setCellValue($ws,$colPhone,$row,$phone);

            $shirtSize = $this->shirtSizeTransformer->transform(($regPerson->shirtSize));
            $this->setCellValue($ws,$colShirt,$row,$shirtSize);

            $row++;
        }
    }
    private function setCellValueStat($ws,$col,$row,$stat)
    {
        if (!$stat) return;
        $this->setCellValue($ws,$col,$row,$stat);
    }
    private function generateStats(RegPerson $regPerson, $gameOfficialsMap)
    {
        $stats = [
            'slotAll' => 0,
            'slotRef' => 0,
            'slotAr'  => 0,

            'yc' => 0,
            'rc' => 0,

            'wed' => 0,
            'thu' => 0,
            'fri' => 0,
            'sat' => 0,
            'sun' => 0,
        ];
        $phyPersonId = $regPerson->personId;
        $gameOfficials = isset($gameOfficialsMap[$phyPersonId]) ? $gameOfficialsMap[$phyPersonId] : null;
        if (!$gameOfficials) {
            return $stats;
        }
        /** @var GameOfficial $gameOfficial */
        foreach($gameOfficials as $gameOfficial) {
            $stats['slotAll']++;
            switch($gameOfficial->slot) {
                case 1:
                    $stats['slotRef']++;
                    break;
                case 2:
                case 3:
                    $stats['slotAr']++;
                    break;
            }
            // Games per day
            $dow = strtolower($gameOfficial->game->dow);
            $stats[$dow]++;
            
            // Cards
            foreach($gameOfficial->game->getTeams() as $gameTeam) {
                $stats['yc'] += $gameTeam->playerWarnings;
                $stats['rc'] += $gameTeam->playerEjections;
            }
        }
        return $stats;
    }
}