<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\AbstractActionTrait;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\GameOfficial\GameOfficialDetailsFinder;
use AppBundle\Action\RegPerson\RegPersonFinder;

class ScheduleTemplate
{
    use AbstractActionTrait;


    protected $showOfficials;
    protected $showOfficialDetails;
    protected $gameOfficialDetailsFinder;

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
        $this->showOfficials       = $showOfficials;
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
    public function render(array $games)
    {
        $gameCount = count($games);

        $html =  <<<EOD
<div id="layout-block clear-fix">
<div class="schedule-games-list">
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
        $this->regPersonTeamIds    = $this->regPersonFinder->findRegPersonTeamIds  ($regPersonId);
        $this->regPersonPersonsIds = $this->regPersonFinder->findRegPersonPersonIds($regPersonId);

        $html = null;

        foreach($games as $game) {

            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

            $homeTeamName = $this->escape($homeTeam->regTeamName);
            $awayTeamName = $this->escape($awayTeam->regTeamName);

            if (isset($this->regPersonTeamIds[$homeTeam->regTeamId])) {
                $homeTeamName = sprintf('<span class="my-team">%s</span>',$homeTeamName);
            }
            if (isset($this->regPersonTeamIds[$awayTeam->regTeamId])) {
                $awayTeamName = sprintf('<span class="my-team">%s</span>',$awayTeamName);
            }
            // Id for returning
            $trId = 'game-' . $game->gameId;

            // Link for editing game
            $gameNumber = $game->gameNumber;
            if ($this->isGranted('ROLE_USER')) {
                $params = [
                    'projectId'  => $game->projectId,
                    'gameNumber' => $game->gameNumber,
                    'back' => $this->getCurrentRouteName(), // $this->generateUrl($this->getCurrentRouteName()) . '#' . $trId,
                ];
                $url = $this->generateUrl('game_report_update',$params);

                $gameNumber = sprintf('<a href="%s">%s</a>',$url,$gameNumber);
            }
            $html .= <<<EOD
<tr id="{$trId}" class="game-status-{$game->status}">
  <td class="schedule-game" >{$gameNumber}</td>
  <td class="schedule-dow"  >{$game->dow}</td>
  <td class="schedule-time" >{$game->time}</td>
  <td class="schedule-field"><a href="{$this->generateUrl('field_map')}" target=_blank}>{$game->fieldName}</a></td>
  <td class="schedule-group">{$game->poolView}</td>
  <td>{$homeTeam->poolTeamSlotView}<hr class="separator">{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$homeTeamName}<hr class="separator">{$awayTeamName}</td>
EOD;
            // TODO: Move all this to a ScheduleOfficialTemplate
            if ($this->showOfficials) {
                $html .= <<<EOD
  <td class="schedule-referees text-left">
    <table>
      <tr>{$this->renderGameOfficial($game,$game->referee)}</tr>
      <tr>{$this->renderGameOfficial($game,$game->ar1)    }</tr>
      <tr>{$this->renderGameOfficial($game,$game->ar2)    }</tr>
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
            $gameOfficialName = sprintf('<span class="%s">%s</span>',$class,$gameOfficialName);
        }
        $params = [
            'projectId'  => $game->projectId,
            'gameNumber' => $game->gameNumber,
            'slot'       => $gameOfficial->slot,
            'back'       => $this->getCurrentRouteName(),
        ];
        $assignRouteName = $this->showOfficialDetails ?
            'game_official_assign_by_assignor':
            'game_official_assign_by_assignee';

        $assignUrl = $this->generateUrl($assignRouteName,$params);

        $slotView = $gameOfficial->slotView;
        if ($this->isGranted('edit',$gameOfficial)) {
            $slotView = sprintf('<a href="%s">%s</a>',$assignUrl,$slotView);
        }

        $assignState = $gameOfficial->assignState;
        $assignStateView = $this->assignWorkflow->assignStateAbbreviations[$assignState];

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
        $refereeBadge = substr($row['refereeBadge'],0,3);

        return $html . <<<EOD
        <td class="text-left">{$refereeBadge}</td>
        <td class="text-left">{$row['orgView']}</td>
EOD;

    }
}
