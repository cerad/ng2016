<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\AbstractActionTrait;
use AppBundle\Action\GameOfficial\AssignWorkflow;

class ScheduleTemplate
{
    use AbstractActionTrait;

    protected $showOfficials;

    protected $scheduleTitle;

    protected $assignWorkflow;

    public function __construct($showOfficials = false, AssignWorkflow $assignWorkflow)
    {
        $this->showOfficials = $showOfficials;

        $this->scheduleTitle = 'Game Schedule';

        $this->assignWorkflow = $assignWorkflow;
    }
    /**
     * @param  ScheduleGame[] $games
     * @return string
     */
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
        $html = null;

        foreach($games as $game) {

            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

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
  <td class="text-left">{$homeTeam->regTeamName}<hr class="separator">{$awayTeam->regTeamName}</td>
EOD;
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
        $params = [
            'projectId'  => $game->projectId,
            'gameNumber' => $game->gameNumber,
            'slot'       => $gameOfficial->slot,
            'back'       => $this->getCurrentRouteName(),
        ];
        $url = $this->generateUrl('game_official_assign_by_assignee',$params);
        $slotView = $gameOfficial->slotView;
        if ($this->isGranted('edit',$gameOfficial)) {
            $slotView = '<a href="{$url}">{$slotView}</a>';
        }

        $assignState = $gameOfficial->assignState;
        $assignStateView = $this->assignWorkflow->assignStateAbbreviations[$assignState];

        return <<<EOD
        <td class="text-left game-official-state-{$assignState}">{$assignStateView}</td>
        <td class="text-left">{$slotView}</td>
        <td class="text-left">{$this->escape($gameOfficial->regPersonName)}</td>

EOD;
    }
}
