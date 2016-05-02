<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\AbstractActionTrait;
use AppBundle\Action\Schedule2016\ScheduleGame;

class ScheduleTemplate
{
    use AbstractActionTrait;

    /**
     * @param  ScheduleGame[] $games
     * @return string
     */
    public function render(array $games)
    {
        $gameCount = count($games);

        return <<<EOD
<div id="layout-block">
<div class="schedule-games-list">
<table id="schedule" class="schedule" border="1">
  <thead>
    <tr><th colspan="20" class="text-center">Game Schedule - Game Count: {$gameCount}</th></tr>
    <tr>
      <th class="schedule-game" >Game</th>
      <th class="schedule-dow"  >Day</th>
      <th class="schedule-time" >Time</th>
      <th class="schedule-field">Field</th>
      <th class="schedule-group">Group</th>
      <th class="schedule-slot" >Slot</th>
      <th class="schedule-teams">Home / Away</th>
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

            $trId = 'schedule-' . $game->id;

            // Link for editing game
            $gameNumber = $game->gameNumber;
            if ($this->isGranted('ROLE_USER')) {
                $params = [
                    'gameNumber' => $game->id,
                    'back' => $this->generateUrl($this->getCurrentRouteName()) . '#' . $trId,
                ];
                $url = $this->generateUrl('game_report_update',$params);
                
                $gameNumber = sprintf('<a href="%s">%s</a>',$url,$gameNumber);
            }
            $html .= <<<EOD
<tr id="{$trId}" class="game-status-{$game->status}">
  <td class="schedule-game" >{$gameNumber}</td>
  <td class="schedule-dow"  >{$game->dow}</td>
  <td class="schedule-time" >{$game->time}</td>
  <td class="schedule-field">{$game->fieldName}</td>
  <td class="schedule-group">{$game->poolView}</td>
  <td>{$homeTeam->poolTeamSlotView}<hr class="separator">{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$homeTeam->name}<hr class="separator">{$awayTeam->name}</td>
</tr>
EOD;
        }
        return $html;
    }
}