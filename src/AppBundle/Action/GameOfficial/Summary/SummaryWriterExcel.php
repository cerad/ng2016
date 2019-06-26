<?php

namespace AppBundle\Action\GameOfficial\Summary;

use Exception;
use AppBundle\Action\Game\Game;
use AysoBundle\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\ShirtSizeTransformer;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\RegPerson\RegPerson;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Common\ExcelWriterTrait;
use AppBundle\Common\ExcelConstants;

use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryWriterExcel
{
    use ExcelWriterTrait;

    /**
     * @var PhoneTransformer
     */
    private $phoneTransformer;
    /**
     * @var ShirtSizeTransformer
     */
    private $shirtSizeTransformer;
    /**
     * @var RegionToSarTransformer
     */
    private $orgTransformer;

    /**
     * @var AssignWorkflow
     */
    private $assignWorkflow;

    /**
     * SummaryWriterExcel constructor.
     * @param RegionToSarTransformer $orgTransformer
     * @param PhoneTransformer $phoneTransformer
     * @param ShirtSizeTransformer $shirtSizeTransformer
     * @param AssignWorkflow $assignWorkflow
     */
    public function __construct(
        RegionToSarTransformer $orgTransformer,
        PhoneTransformer $phoneTransformer,
        ShirtSizeTransformer $shirtSizeTransformer,
        AssignWorkflow $assignWorkflow
    ) {
        $this->assignWorkflow = $assignWorkflow;

        $this->phoneTransformer = $phoneTransformer;
        $this->shirtSizeTransformer = $shirtSizeTransformer;
        $this->orgTransformer = $orgTransformer;
    }

    /**
     * @param RegPerson[] $regPersons
     * @param Game[] $games
     * @return string
     * @throws PhpSpreadsheet\Exception
     */
    public function write(array $regPersons, array $games)
    {
        // Only referees

        $regPersons = array_filter(
            $regPersons,
            function (RegPerson $regPerson) {
                if ($regPerson->isReferee) {
                    return true;
                }

                return false;
            }
        );

        usort(
            $regPersons,
            function (RegPerson $regPerson1, RegPerson $regPerson2) {
                //sort on last name
                $name1 = explode(' ', $regPerson1->name);
                $name1 = array_pop($name1);
                $name2 = explode(' ', $regPerson2->name);
                $name2 = array_pop($name2);

                return strcmp(strtolower($name1), strtolower($name2));
            }
        );

        usort(
            $games,
            function (Game $game1, Game $game2) {
                if ($game1->start < $game2->start) {
                    return -1;
                }
                if ($game1->start > $game2->start) {
                    return +1;
                }

                return 0;
            }
        );

        $gameOfficialsMap = $this->generateGameOfficialsMap($games);

        $wb = $this->createWorkBook();
        $ws = $wb->getActiveSheet();

        $this->writeSummary($ws, $regPersons, $gameOfficialsMap);

        $ws = $wb->createSheet(1);
        $this->writeGames($ws, $regPersons, $gameOfficialsMap);

        $wb->setActiveSheetIndex(0);

        return $this->getContents();
    }

    /**
     * @param Game[] $games
     * @return array
     */
    private function generateGameOfficialsMap($games)
    {
        $gameOfficialsMap = [];
        foreach ($games as $game) {
            foreach ($game->getOfficials() as $gameOfficial) {
                if ($gameOfficial->phyPersonId) {
                    $gameOfficial->game = $game;
                    $gameOfficialsMap[$gameOfficial->phyPersonId][] = $gameOfficial;
                }
            }
        }

        return $gameOfficialsMap;
    }

    /** =========================================
     * @param Worksheet $ws
     * @param RegPerson[] $regPersons
     * @param array $gameOfficialsMap
     * @throws Exception
     * @throws PhpSpreadsheet\Exception
     */
    private function writeGames(Worksheet $ws, $regPersons, $gameOfficialsMap)
    {
        $ws->setTitle('Referee Games');

        $col = 'A';
        $colOfficialName = $col++;
        $colOfficialBadge = $col++;
        $colOfficialSars = $col++;
        $colOfficialAge = $col++;
        $colOfficialState = $col++;
        $colOfficialSlot = $col++;

        $colGameNumber = $col++;
        $colGameDate = $col++;
        $colGameTime = $col++;
        $colFieldName = $col++;

        $colHomeTeamPoolKey = $col++;
        $colHomeTeamName = $col++;
        $colAwayTeamName = $col++;
        $colAwayTeamPoolKey = $col;

        $this->setColAlignment($ws, $colGameNumber);
        $this->setColAlignment($ws, $colGameDate);
        $this->setColAlignment($ws, $colGameTime);
        $this->setColAlignment($ws, $colOfficialAge);

//        $this->setColWidth($ws, $colOfficialName, 36);
        $this->setColWidth($ws, $colOfficialBadge, 6);
        $this->setColWidth($ws, $colOfficialSars, 16);
        $this->setColWidth($ws, $colOfficialAge, 5);
        $this->setColWidth($ws, $colOfficialState, 6);
        $this->setColWidth($ws, $colOfficialSlot, 6);
        $this->setColWidth($ws, $colGameNumber, 8);
        $this->setColWidth($ws, $colGameDate, 6);
        $this->setColWidth($ws, $colGameTime, 10);
        $this->setColWidth($ws, $colFieldName, 10);
        $this->setColWidth($ws, $colHomeTeamPoolKey, 20);
        $this->setColWidth($ws, $colHomeTeamName, 30);
        $this->setColWidth($ws, $colAwayTeamName, 30);
        $this->setColWidth($ws, $colAwayTeamPoolKey, 20);

        $row = 1;
        $this->setCellValue($ws, $colOfficialName, $row, 'Referee Name');
        $this->setCellValue($ws, $colOfficialBadge, $row, 'Badge');
        $this->setCellValue($ws, $colOfficialSars, $row, 'S/A/R/St');
        $this->setCellValue($ws, $colOfficialAge, $row, 'Age');
        $this->setCellValue($ws, $colOfficialState, $row, 'State');
        $this->setCellValue($ws, $colOfficialSlot, $row, 'Slot');
        $this->setCellValue($ws, $colGameNumber, $row, 'Game');
        $this->setCellValue($ws, $colGameDate, $row, 'Date');
        $this->setCellValue($ws, $colGameTime, $row, 'Time');
        $this->setCellValue($ws, $colFieldName, $row, 'Field');
        $this->setCellValue($ws, $colHomeTeamPoolKey, $row, 'Home Team Pool');
        $this->setCellValue($ws, $colHomeTeamName, $row, 'Home Team Name');
        $this->setCellValue($ws, $colAwayTeamName, $row, 'Away Team Name');
        $this->setCellValue($ws, $colAwayTeamPoolKey, $row, 'Away Team Pool');
        $ws->freezePane('A2');

        $row = 2;
        foreach ($regPersons as $regPerson) {
            if (isset($gameOfficialsMap[$regPerson->personId])) {
                $row++;
                /** @var GameOfficial $gameOfficial */
                foreach ($gameOfficialsMap[$regPerson->personId] as $gameOfficial) {
                    $this->setCellValue($ws, $colOfficialName, $row, ucwords(mb_strtolower($regPerson->name)));
                    switch ($regPerson->refereeBadge) {
                        case 'None':
                            $this->setCellValue($ws, $colOfficialBadge, $row, $regPerson->refereeBadge);
                            break;
                        default:
                            $this->setCellValue($ws, $colOfficialBadge, $row, substr($regPerson->refereeBadge, 0, 3));
                    }

                    $orgView = $this->orgTransformer->transform(($regPerson->orgId));
                    switch ($orgView) {
                        case null:
                        case '0':
                            $$orgView = '';
                    }
                    $this->setCellValue($ws, $colOfficialSars, $row, $orgView);
                    $this->setCellValue($ws, $colOfficialAge, $row, $regPerson->age);

                    $assignStateView = $this->assignWorkflow->assignStateAbbreviations[$gameOfficial->assignState];
                    switch ($assignStateView) {
                        case 'Acc':
                        case 'App':
                            $this->setCellFillColor(
                                $ws,
                                $colOfficialState,
                                $row,
                                ExcelConstants::COLOR_GREEN
                            );
                    }
                    $this->setCellValue($ws, $colOfficialState, $row, $assignStateView);

                    $this->setCellValue($ws, $colOfficialSlot, $row, $gameOfficial->slotView);

                    $game = $gameOfficial->game;
                    $this->setCellValue($ws, $colGameNumber, $row, $game->gameNumber);
                    $this->setCellValue($ws, $colFieldName, $row, $game->fieldName);

                    $this->setCellValueDate($ws, $colGameDate, $row, $game->start);
                    $this->setCellValueTime($ws, $colGameTime, $row, $game->start);

                    $homeTeam = $game->homeTeam;
                    $awayTeam = $game->awayTeam;

                    $this->setCellValue($ws, $colHomeTeamPoolKey, $row, $homeTeam->poolTeamKey);
                    $this->setCellValue($ws, $colHomeTeamName, $row, $homeTeam->regTeamName);

                    $this->setCellValue($ws, $colAwayTeamPoolKey, $row, $awayTeam->poolTeamKey);
                    $this->setCellValue($ws, $colAwayTeamName, $row, $awayTeam->regTeamName);

                    $row++;
                }
            }
        }

        $this->setColAutoSize($ws, $colOfficialName);

    }

    /**
     * @param Worksheet $ws
     * @param RegPerson[] $regPersons
     * @param array $gameOfficialsMap
     * @throws Exception
     * @throws PhpSpreadsheet\Exception
     */
    private function writeSummary(Worksheet $ws, $regPersons, $gameOfficialsMap)
    {
        $ws->setTitle('Referee Summary');

        $col = 'A';
        $colRegPersonName = $col++;

        $colStatSlotAll = $col++;
        $colStatSlotRef = $col++;
        $colStatSlotAr = $col++;
        $colStatSlotYc = $col++;
        $colStatSlotRc = $col++;
        $colSkip1 = $col++;
        $colStatSlotTue = $col++;
        $colStatSlotWed = $col++;
        $colStatSlotThu = $col++;
        $colStatSlotFri = $col++;
        $colStatSlotSat = $col++;
        $colStatSlotSun = $col++;
        $colSkip2 = $col++;

        $colAvailSlotTue = $col++;
        $colAvailSlotWed = $col++;
        $colAvailSlotThu = $col++;
        $colAvailSlotFri = $col++;
        $colAvailSlotSat1 = $col++;
        $colAvailSlotSat2 = $col++;
        $colAvailSlotSun1 = $col++;
        $colAvailSlotSun2 = $col++;
        $colSkip3 = $col++;

        $colBadge = $col++;
        $colSars = $col++;
        $colAge = $col++;
        $colEmail = $col++;
        $colPhone = $col++;
        $colShirt = $col;


        $this->setColAlignment($ws, $colAge);
        $this->setColAlignment($ws, $colBadge);

        $this->setColAlignment($ws, $colStatSlotAll);
        $this->setColAlignment($ws, $colStatSlotRef);
        $this->setColAlignment($ws, $colStatSlotAr);
        $this->setColAlignment($ws, $colStatSlotYc);
        $this->setColAlignment($ws, $colStatSlotRc);

        $this->setColAlignment($ws, $colStatSlotTue);
        $this->setColAlignment($ws, $colStatSlotWed);
        $this->setColAlignment($ws, $colStatSlotThu);
        $this->setColAlignment($ws, $colStatSlotFri);
        $this->setColAlignment($ws, $colStatSlotSat);
        $this->setColAlignment($ws, $colStatSlotSun);

        $this->setColAlignment($ws, $colAvailSlotTue);
        $this->setColAlignment($ws, $colAvailSlotWed);
        $this->setColAlignment($ws, $colAvailSlotThu);
        $this->setColAlignment($ws, $colAvailSlotFri);
        $this->setColAlignment($ws, $colAvailSlotSat1);
        $this->setColAlignment($ws, $colAvailSlotSat2);
        $this->setColAlignment($ws, $colAvailSlotSun1);
        $this->setColAlignment($ws, $colAvailSlotSun2);
        $this->setColAlignment($ws, $colSars, PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

//        $this->setColWidth($ws,$colRegPersonName,24);
        $this->setColWidth($ws, $colBadge, 6);
        $this->setColWidth($ws, $colSars, 16);
        $this->setColWidth($ws, $colAge, 5);
        $this->setColWidth($ws, $colEmail, 32);
        $this->setColWidth($ws, $colPhone, 14);
        $this->setColWidth($ws, $colShirt, 6);

        $this->setColWidth($ws, $colStatSlotAll, 5);
        $this->setColWidth($ws, $colStatSlotRef, 5);
        $this->setColWidth($ws, $colStatSlotAr, 5);
        $this->setColWidth($ws, $colStatSlotYc, 5);
        $this->setColWidth($ws, $colStatSlotRc, 5);
        $this->setColWidth($ws, $colSkip1, 5);
        $this->setColWidth($ws, $colStatSlotTue, 5);
        $this->setColWidth($ws, $colStatSlotWed, 5);
        $this->setColWidth($ws, $colStatSlotThu, 5);
        $this->setColWidth($ws, $colStatSlotFri, 5);
        $this->setColWidth($ws, $colStatSlotSat, 5);
        $this->setColWidth($ws, $colStatSlotSun, 5);
        $this->setColWidth($ws, $colSkip2, 5);
        $this->setColWidth($ws, $colAvailSlotTue, 6);
        $this->setColWidth($ws, $colAvailSlotWed, 6);
        $this->setColWidth($ws, $colAvailSlotThu, 6);
        $this->setColWidth($ws, $colAvailSlotFri, 6);
        $this->setColWidth($ws, $colAvailSlotSat1, 8);
        $this->setColWidth($ws, $colAvailSlotSat2, 8);
        $this->setColWidth($ws, $colAvailSlotSun1, 8);
        $this->setColWidth($ws, $colAvailSlotSun2, 8);
        $this->setColWidth($ws, $colSkip3, 5);

        $this->setColWidth($ws, $colEmail, 40);
        $this->setColWidth($ws, $colPhone, 18);
        $this->setColWidth($ws, $colShirt, 8);


        $row = 1;
        $this->setCellValue($ws, $colRegPersonName, $row, 'Name');
        $this->setCellValue($ws, $colBadge, $row, 'Badge');
        $this->setCellValue($ws, $colSars, $row, 'S/A/R/St');
        $this->setCellValue($ws, $colAge, $row, 'Age');
        $this->setCellValue($ws, $colEmail, $row, 'Email');
        $this->setCellValue($ws, $colPhone, $row, 'Phone');
        $this->setCellValue($ws, $colShirt, $row, 'Shirt');

        $this->setCellValue($ws, $colStatSlotAll, $row, 'ALL');
        $this->setCellValue($ws, $colStatSlotRef, $row, 'REF');
        $this->setCellValue($ws, $colStatSlotAr, $row, 'AR');
        $this->setCellValue($ws, $colStatSlotYc, $row, 'YC');
        $this->setCellValue($ws, $colStatSlotRc, $row, 'RC');
        $this->setCellValue($ws, $colStatSlotTue, $row, 'TUE');
        $this->setCellValue($ws, $colStatSlotWed, $row, 'WED');
        $this->setCellValue($ws, $colStatSlotThu, $row, 'THU');
        $this->setCellValue($ws, $colStatSlotFri, $row, 'FRI');
        $this->setCellValue($ws, $colStatSlotSat, $row, 'SAT');
        $this->setCellValue($ws, $colStatSlotSun, $row, 'SUN');

        $this->setCellValue($ws, $colAvailSlotTue, $row, 'Tue');
        $this->setCellValue($ws, $colAvailSlotWed, $row, 'Wed');
        $this->setCellValue($ws, $colAvailSlotThu, $row, 'Thu');
        $this->setCellValue($ws, $colAvailSlotFri, $row, 'Fri');
        $this->setCellValue($ws, $colAvailSlotSat1, $row, 'Sat AM');
        $this->setCellValue($ws, $colAvailSlotSat2, $row, 'Sat PM');
        $this->setCellValue($ws, $colAvailSlotSun1, $row, 'Sun AM');
        $this->setCellValue($ws, $colAvailSlotSun2, $row, 'Sun PM');

        $ws->freezePane('B2');

        $row = 2;
        foreach ($regPersons as $regPerson) {

            $stats = $this->generateStats($regPerson, $gameOfficialsMap);

            $this->setCellValue($ws, $colRegPersonName, $row, ucwords(mb_strtolower($regPerson->name)));
            switch ($regPerson->refereeBadge) {
                case 'None':
                    $this->setCellValue($ws, $colBadge, $row, $regPerson->refereeBadge);
                    break;
                default:
                    $this->setCellValue($ws, $colBadge, $row, substr($regPerson->refereeBadge, 0, 3));
            }

            $this->setCellValueStat($ws, $colStatSlotAll, $row, $stats['slotAll']);
            $this->setCellValueStat($ws, $colStatSlotRef, $row, $stats['slotRef']);
            $this->setCellValueStat($ws, $colStatSlotAr, $row, $stats['slotAr']);

            $this->setCellValueStat($ws, $colStatSlotYc, $row, $stats['yc']);
            $this->setCellValueStat($ws, $colStatSlotRc, $row, $stats['rc']);

            $this->setCellValueStat($ws, $colStatSlotTue, $row, $stats['tue']);
            $this->setCellValueStat($ws, $colStatSlotWed, $row, $stats['wed']);
            $this->setCellValueStat($ws, $colStatSlotThu, $row, $stats['thu']);
            $this->setCellValueStat($ws, $colStatSlotFri, $row, $stats['fri']);
            $this->setCellValueStat($ws, $colStatSlotSat, $row, $stats['sat']);
            $this->setCellValueStat($ws, $colStatSlotSun, $row, $stats['sun']);

            foreach ($regPerson->avail as $key => $value) {
                switch (strtolower($value)) {
                    case 'yes':
                    case 'maybe':
                        switch ($key) {
                            case 'availTue':
                                $this->setCellValueStat($ws, $colAvailSlotTue, $row, 'Y');
                                break;
                            case 'availWed':
                                $this->setCellValueStat($ws, $colAvailSlotWed, $row, 'Y');
                                break;
                            case 'availThu':
                                $this->setCellValueStat($ws, $colAvailSlotThu, $row, 'Y');
                                break;
                            case 'availFri':
                                $this->setCellValueStat($ws, $colAvailSlotFri, $row, 'Y');
                                break;
                            case 'availSatMorn':
                                $this->setCellValueStat($ws, $colAvailSlotSat1, $row, 'Y');
                                break;
                            case 'availSatAfter':
                                $this->setCellValueStat($ws, $colAvailSlotSat2, $row, 'Y');
                                break;
                            case 'availSunMorn':
                                $this->setCellValueStat($ws, $colAvailSlotSun1, $row, 'Y');
                                break;
                            case 'availSunAfter':
                                $this->setCellValueStat($ws, $colAvailSlotSun2, $row, 'Y');
                                break;

                        }
                        break;
                }
            }


            $orgView = $this->orgTransformer->transform(($regPerson->orgId));
            switch ($orgView) {
                case null:
                case '0':
                    $orgView = '';
            }
            $this->setCellValue($ws, $colSars, $row, $orgView);

            $this->setCellValue($ws, $colAge, $row, $regPerson->age);
            $this->setCellValue($ws, $colEmail, $row, mb_strtolower($regPerson->email));

            $phone = $this->phoneTransformer->transform(($regPerson->phone));
            $this->setCellValue($ws, $colPhone, $row, $phone);

            $shirtSize = $this->shirtSizeTransformer->transform(($regPerson->shirtSize));
            $this->setCellValue($ws, $colShirt, $row, $shirtSize);

            $row++;
        }

        $this->setColAutoSize($ws, $colRegPersonName);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $stat
     */
    private function setCellValueStat($ws, $col, $row, $stat)
    {
        if (!$stat) {
            return;
        }

        $ws->setCellValue($col.$row, $stat);
    }

    /**
     * @param RegPerson $regPerson
     * @param $gameOfficialsMap
     * @return array
     */
    private function generateStats(RegPerson $regPerson, $gameOfficialsMap)
    {
        $stats = [
            'slotAll' => 0,
            'slotRef' => 0,
            'slotAr' => 0,

            'yc' => 0,
            'rc' => 0,

            'tue' => 0,
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
        foreach ($gameOfficials as $gameOfficial) {
            $stats['slotAll']++;
            switch ($gameOfficial->slot) {
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
            foreach ($gameOfficial->game->getTeams() as $gameTeam) {
                $stats['yc'] += $gameTeam->playerWarnings;
                $stats['rc'] += $gameTeam->playerEjections;
            }
        }

        return $stats;
    }
}