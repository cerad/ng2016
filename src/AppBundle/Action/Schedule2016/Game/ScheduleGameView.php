<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule2016\ScheduleGame;
use Symfony\Component\HttpFoundation\Request;

class ScheduleGameView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;
    
    private $project;
    private $search;
    private $searchControls;
    private $currentRouteName;

    public function __invoke(Request $request)
    {
        $this->currentRouteName = $request->attributes->get('_route');

        $this->games  = $request->attributes->get('games');
        $this->search = $request->attributes->get('search');

        $this->project = $this->getCurrentProjectInfo();

        $this->searchControls = $this->project['search_controls'];

        return $this->newResponse($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
{$this->renderSearchForm()}
<br />
{$this->renderSchedule()}
<br />
EOD;
        $script = <<<EOD
<script type="text/javascript">
$(document).ready(function() {
    // checkbox all functionality
    $('.cerad-checkbox-all').change(Cerad.checkboxAll);
});
</script>
EOD;

        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        $baseTemplate->addScript ($script);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderSearchForm()
    {
        $html = <<<EOD
<div class="container">
<form method="post" action="{$this->generateUrl($this->currentRouteName)}" class="cerad_common_form1">
  <fieldset>
    <table id="schedule-select"><tr>
EOD;
        foreach($this->searchControls as $key => $params) {

            $label = $params['label'];
            $html .= <<<EOD
    <td>{$this->renderSearchCheckbox($key, $label, $this->project[$key], $this->search[$key])}</td>
EOD;
        }
        $xlsUrl = $this->generateUrl($this->currentRouteName,['_format' => 'xls']);
        $csvUrl = $this->generateUrl($this->currentRouteName,['_format' => 'csv']);
        $shareSpan = '<span class="glyphicon glyphicon-share"></span>';

        $html .= <<<EOD
    </tr></table>
    <div class="col-xs-10">
      <div class="row float-right">
        <button type="submit" id="form_search" class="btn btn-sm btn-primary submit">Search</button>
        <a href="{$xlsUrl}" class="btn btn-sm btn-primary">{$shareSpan}Export to Excel</a> 
        <a href="{$csvUrl}" class="btn btn-sm btn-primary">{$shareSpan}Export to CSV</a> 
      </div>
    </div>
    <div class="clear-both"></div>
    <br/>
    <legend></legend>
  </fieldset>
</form>
EOD;
        return $html;
    }
    protected function renderSearchCheckbox($name,$label,$items,$selected)
    {
        $html = <<<EOD
<table>
  <tr><th colspan="30">{$label}</th></tr>
    <td align="center">All<br />
    <input type="checkbox" name="search[{$name}][]" class="cerad-checkbox-all" value="All" /></td>
EOD;
        foreach($items as $value => $label) {
            $checked = in_array($value, $selected) ? ' checked' : null;
            $html .= <<<EOD
    <td align="center">{$label}<br />
    <input type="checkbox" name="search[{$name}][]" value="{$value}"{$checked} /></td>
EOD;
        }
        $html .= <<<EOD
  </tr>
</table>
</div>
EOD;
        return $html;
    }
    /* ============================================================
     * Render games
     */
    protected function renderSchedule()
    {
        $gameCount = count($this->games);

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
  {$this->renderScheduleRows()}
  </tbody>
</table>
</div>
<br />
</div
EOD;
    }
    protected function renderScheduleRows()
    {
        $html = null;

        foreach($this->games as $game) {

            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;

            $trId = 'schedule-' . $game->id;

            // Link for editing game
            $gameNumber = $game->gameNumber;
            if ($this->isGranted('ROLE_USER')) {
                $params = [
                    'gameNumber' => $game->id,
                    'back' => $this->generateUrl($this->currentRouteName) . '#' . $trId,
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
