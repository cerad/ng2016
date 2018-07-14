<?php

namespace AppBundle\Action\Schedule;

use AppBundle\Action\AbstractActionTrait;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\GameOfficial\GameOfficialDetailsFinder;
use AppBundle\Action\RegPerson\RegPersonFinder;
use AppBundle\Action\AbstractTemplate;

class ScheduleTemplate extends AbstractTemplate
{
    use AbstractActionTrait;


    protected $showOfficials;
    protected $showOfficialDetails;
    protected $gameOfficialDetailsFinder;
    private $certifications;

    protected $scheduleTitle;

    protected $assignWorkflow;

    /** @var  RegPersonFinder */
    protected $regPersonFinder;
    protected $regPersonId;
    protected $regPersonTeamIds;
    protected $regPersonPersonsIds;

    public function __construct(
        $scheduleTitle,
        $showOfficials = false,
        $showOfficialDetails = false,
        AssignWorkflow $assignWorkflow = null,
        GameOfficialDetailsFinder $gameOfficialDetailsFinder = null
    ) {
        $this->showOfficials = $showOfficials;
        $this->showOfficialDetails = $showOfficialDetails;
        $this->gameOfficialDetailsFinder = $gameOfficialDetailsFinder;

        $this->scheduleTitle = $scheduleTitle;

        $this->assignWorkflow = $assignWorkflow;
    }

    public function setRegPersonFinder(RegPersonFinder $regPersonFinder)
    {
        $this->regPersonFinder = $regPersonFinder;
    }

    // Depreciated
    public function setTitle($title = 'Game Schedule')
    {
        $this->scheduleTitle = $title;
    }

    public function render(array $games, array $certifications = null)
    {
        $gameCount = count($games);
        $this->certifications = $certifications;

        $this->showOfficials = $this->isGranted('ROLE_REFEREE') ? $this->showOfficials : false;
        $this->showOfficialDetails = $this->isGranted('ROLE_REFEREE') ? $this->showOfficialDetails : false;

        $html = null;

        if ($this->showOfficialDetails) { // for Assignor Instructions
            $html .= null;

        } elseif ($this->showOfficials) {  // for Referee instructions
            $html .=
                <<<EOT
<div id="clear-fix">
    <legend>Instructions for Referees</legend>
      <ul class="cerad-common-help ul_bullets">
            <li>Click on "<a href="{$this->generateUrl('schedule_official_2018')}">Request Assignments</a>" under the "Referees" menu item above.</li>
            <li>On any open match, click on the position you'd like to request, e.g. REF, AR1, AR2</li>
            <li>Click "Submit" button"</li>
            <li>Check back on your schedule under "<a href="{$this->generateUrl('schedule_my_2018')}">My Schedule</a>" under the "My Stuff" menu item above to see the assignments.
            <li>Detailed instructions for self-assigning are available <a href="{$this->generateUrl(
                    'detailed_instruction'
                )}" target="_blank">by clicking here</a>.</ul>
      </ul>
</div>
<hr>
EOT;
        }

        $html .= <<<EOD
<table id="schedule" class="schedule" border="1">
  <thead>
    <tr><th colspan="20" class="text-center">{$this->scheduleTitle} - Game Count: {$gameCount}</th></tr>
    <tr>
      <th class="schedule-game" >Game</th>
      <th class="schedule-dow"  >Day</th>
      <th class="schedule-time" >Time</th>
      <th class="schedule-field">Field</th>
      <th class="schedule-group">Group</th>
      <th class="schedule-slot" >Slot</th>
      <th class="schedule-teams">Home / Away</th>
EOD;
        if ($this->showOfficials) {
            $html .= <<<EOD
      <th class="schedule-officials">Officials</th>
EOD;
        }
        $html .= <<<EOD
    </tr>
  </thead>
  <tbody>
  {$this->renderScheduleRows($games)}
  </tbody>
</table>
</div>
<br />
</div
EOD;

        return $html;
    }

    /**
     * @param  ScheduleGame[] $games
     * @return string
     */
    private function renderScheduleRows($games)
    {
        $this->regPersonId = $regPersonId = $this->getUserRegPersonId();
        $this->regPersonTeamIds = $this->regPersonFinder->findRegPersonTeamIds($regPersonId);
        $this->regPersonPersonsIds = $this->regPersonFinder->findRegPersonPersonIds($regPersonId);

        $html = null;

        foreach ($games as $game) {

            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

            $homeTeamName = $this->escape($homeTeam->regTeamName);
            $awayTeamName = $this->escape($awayTeam->regTeamName);

            if (isset($this->regPersonTeamIds[$homeTeam->regTeamId])) {
                $homeTeamName = sprintf('<span class="my-team">%s</span>', $homeTeamName);
            }
            if (isset($this->regPersonTeamIds[$awayTeam->regTeamId])) {
                $awayTeamName = sprintf('<span class="my-team">%s</span>', $awayTeamName);
            }
            // Id for returning
            $trId = 'game-'.$game->gameId;

            // Link for editing game
            $gameNumber = $game->gameNumber;
            if ($this->isGranted('ROLE_USER')) {
                $params = [
                    'projectId' => $game->projectId,
                    'gameNumber' => $game->gameNumber,
                    'back' => $this->getCurrentRouteName(),
                    // $this->generateUrl($this->getCurrentRouteName()) . '#' . $trId,
                ];
                $url = $this->generateUrl('game_report_update', $params);

                $gameNumber = sprintf('<a href="%s">%s</a>', $url, $gameNumber);
            }
            $html .= <<<EOD
<tr id="{$trId}" class="game-status-{$game->status}">
  <td class="schedule-game" >{$gameNumber}</td>
  <td class="schedule-dow"  >{$game->dow}</td>
  <td class="schedule-time" >{$game->time}</td>
  <td class="schedule-field"><a href="{$this->generateUrl('field_map')}" target=_blank}>{$game->fieldName}</a></td>
  <td class="schedule-group">{$game->poolView}</td>
  <td>{$homeTeam->poolTeamSlotView}<hr class="separator">{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$homeTeamName}&nbsp;<hr class="separator">{$awayTeamName}&nbsp;</td>
EOD;
            // TODO: Move all this to a ScheduleOfficialTemplate

            if ($this->showOfficials) {
                $html .= <<<EOD
  <td class="schedule-referees text-left">
    <table>
      <tr>{$this->renderGameOfficial($game, $game->referee)}</tr>
      <tr>{$this->renderGameOfficial($game, $game->ar1)}</tr>
      <tr>{$this->renderGameOfficial($game, $game->ar2)}</tr>
    </table>
  </td>
EOD;
            }
            $html .= <<<EOD
</tr>
EOD;
        }

        return $html;
    }

    private function renderGameOfficial(ScheduleGame $game, ScheduleGameOfficial $gameOfficial)
    {
        // Escape and hilite name
        $gameOfficialName = $this->escape($gameOfficial->regPersonName);
        if (isset($this->regPersonPersonsIds[$gameOfficial->regPersonId])) {
            $class = $gameOfficial->regPersonId === $this->regPersonId ? 'my-slot' : 'my-slot-crew';
            $gameOfficialName = sprintf('<span class="%s">%s</span>', $class, $gameOfficialName);
        }
        $params = [
            'projectId' => $game->projectId,
            'gameNumber' => $game->gameNumber,
            'slot' => $gameOfficial->slot,
            'back' => $this->getCurrentRouteName(),
        ];
        $assignRouteName = $this->showOfficialDetails ?
            'game_official_assign_by_assignor' :
            'game_official_assign_by_assignee';

        $assignUrl = $this->generateUrl($assignRouteName, $params);

        $slotView = $gameOfficial->slotView;
        if ($this->isGranted('view', $gameOfficial)) {
            $slotView = sprintf('<a href="%s">%s</a>', $assignUrl, $slotView);
        }

        if (is_null($gameOfficial->assignState)) {
            var_dump($gameOfficial);
            echo 'gameOfficial assignState = NULL';
            $gameOfficial->assignState = 'Open';
        }
        $assignState = $gameOfficial->assignState;
        try {
            $assignStateView = $this->assignWorkflow->assignStateAbbreviations[$assignState];
        } catch (\Exception $e) {
        }

        if ($assignState === 'Pending' && !$this->isGranted('ROLE_ASSIGNOR')) {
            $assignState = 'Open';
            $assignStateView = 'Open';
            $gameOfficialName = null;
        }

        $html = <<<EOD
        <td class="text-left game-official-state-{$assignState}">{$assignStateView}</td>
        <td class="text-left">{$slotView}</td>
        <td class="text-left">{$gameOfficialName}</td>
EOD;
        if (!$this->showOfficialDetails) {
            return $html;
        }
        $row = $this->gameOfficialDetailsFinder->findGameOfficialDetails($gameOfficial->regPersonId);
        if (!$row) {
            return $html;
        }
        $refereeBadge = substr($row['refereeBadge'], 0, 3);

        return $html.<<<EOD
        <td class="text-left {$this->styleCertificationConflict(
                $refereeBadge,
                $game,
                $gameOfficial->slot
            )}">{$refereeBadge}</td>
        <td class="text-right {$this->styleSectionConflict($row['orgView'], $game)}">{$row['orgView']}</td>
EOD;

    }

    private function styleSectionConflict($refOrg, $game)
    {
        $homeTeam = explode(' ', $game->homeTeam->regTeamName);
        $awayTeam = explode(' ', $game->awayTeam->regTeamName);

        if (isset($homeTeam[1])) {
            $homeTeamOrg = $homeTeam[1];
        } else {
            return null;
        }

        if (isset($awayTeam[1])) {
            $awayTeamOrg = $awayTeam[1];
        } else {
            return null;
        }

        if ($homeTeamOrg == 'BYE') {
            return null;
        }
        if ($awayTeamOrg == 'BYE') {
            return null;
        }

        $homeTeamSection = (integer)explode('-', $homeTeamOrg)[0];
        $awayTeamSection = (integer)explode('-', $awayTeamOrg)[0];

        $refSection = (integer)explode('/', $refOrg)[0];

        if (($homeTeamSection == $refSection) or ($awayTeamSection == $refSection)) {
            return "bg-danger";
        }

        return '';
    }

    private function styleCertificationConflict($refereeBadge, $game, $slot)
    {
        if (is_null($this->certifications)) {
            return '';
        }

        if (!array_key_exists($refereeBadge, $this->certifications)) {
            return "bg-danger";
        }

        if ($slot > 1) {
            return '';
        }

        $div = substr($game->homeTeam->division, 1);
        $cert = $this->certifications[$refereeBadge];

        $ok = $div <= $cert['Core'] || $div >= $cert['Club'] ? '' : "bg-danger";

        return $ok;
    }

}
