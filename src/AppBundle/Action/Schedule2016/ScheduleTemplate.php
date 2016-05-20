<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\AbstractActionTrait;

class ScheduleTemplate
{
    use AbstractActionTrait;

    protected $showOfficials;

    protected $scheduleTitle;

    public function __construct($showOfficials = false)
    {
        $this->showOfficials = $showOfficials;

        $this->scheduleTitle = 'Game Schedule';
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
  <td class="text-left">
    {$this->renderGameOfficial($game,$game->referee)}<hr class="separator">
    {$this->renderGameOfficial($game,$game->ar1)    }<hr class="separator">
    {$this->renderGameOfficial($game,$game->ar2)    }
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

        return <<<EOD
    <a href="{$url}">{$gameOfficial->slotView}</a> {$gameOfficial->regPersonName}
EOD;
    }
}
