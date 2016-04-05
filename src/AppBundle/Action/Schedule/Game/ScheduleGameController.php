<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleGameController extends AbstractController
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }
    public function __invoke(Request $request)
    {
        $project = $this->getCurrentProject()['info'];

        // Save selected teams in session
        $search = $project['search_defaults'];

        $session = $request->getSession();
        if ($session->has('schedule_game_search')) {
            $sessionSearchData = $session->get('schedule_game_search');
            if (is_array($sessionSearchData)) {
                $search = array_merge($search, $sessionSearchData);
            }
        }
        // Search posted
        if ($request->isMethod('POST')) {
            $search = $request->request->get('search');
            $session->set('schedule_game_search',$search);
        }
        return new Response($this->renderPage($project,$search));
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage(array $project, array $search)
    {
        $content = <<<EOD
{$this->renderSearchForm($project,$search)}
<br />
{$this->renderSchedule($search)}
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
        $baseTemplate->addScript ($script);
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderSearchForm(array $project, array $search)
    {
        $html = <<<EOD
<div class="container">
<form method="post" action="{$this->generateUrl('app_schedule_game')}" class="cerad_common_form1">
<fieldset><table id="schedule-select"><tr>
EOD;
        foreach($project['search_controls'] as $key => $params) {
            $label = $params['label'];
            $html .= <<<EOD
  <td>{$this->renderSearchCheckbox($key, $label, $project[$key], $search[$key])}</td>
EOD;
        }
        $html .= <<<EOD
  </tr>
  </table>
          <div class="col-xs-10">
          <div class="row float-right">
      <button type="submit" id="form_search" class="btn btn-sm btn-primary submit">Search</button>
<a href=href="/project/natgames/schedule-game.xls" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Excel</a> 
<a href=href="/project/natgames/schedule-ga,e.csv" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Text</a> 
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
    protected function renderSchedule($criteria)
    {
        $projectGames = $this->scheduleRepository->findProjectGames($criteria);

        $projectGameCount = count($projectGames);

        return <<<EOD
<div id="layout-block">
<div class="schedule-games-list">
<table id="schedule" class="schedule" border="1">
  <thead>
    <tr><th colspan="20" class="text-center">Game Schedule - Game Count: {$projectGameCount}</th></tr>
    <tr>
      <th class="schedule-game" >Game</th>
      <th class="schedule-dow"  >Day</th>
      <th class="schedule-time" >Time</th>
      <th class="schedule-field">Field</th>
      <th class="schedule-group">Group</th>
      <th class="schedule-blank">&nbsp;</th>
      <th class="schedule-slot" >Slot</th>
      <th class="schedule-teams">Home / Away</th>
    </tr>
  </thead>
  <tbody>
  {$this->renderScheduleRows($projectGames)}
  </tbody>
</table>
</div>
<br />
</div
EOD;
    }
    protected function renderScheduleRows($projectGames)
    {
        $html = null;
        foreach($projectGames as $projectGame) {

            $projectGameTeamHome = $projectGame['teams'][1];
            $projectGameTeamAway = $projectGame['teams'][2];

            $html .= <<<EOD
<tr id="schedule-{$projectGame['number']}" class="game-status-{$projectGame['number']}">
  <td class="schedule-game" >{$projectGame['number']}</td>
  <td class="schedule-dow"  >{$projectGame['dow']}</td>
  <td class="schedule-time" >{$projectGame['time']}</td>
  <td class="schedule-field">{$projectGame['field_name']}</td>
  <td class="schedule-group">{$projectGame['group_key']}</td>
  <td>&nbsp;</td>
  <td><table>
    <tr><td>{$projectGameTeamHome['group_slot']}</td></tr>
    <tr><td>{$projectGameTeamAway['group_slot']}</td></tr>
  </table></td>
  <td><table>
    <tr><td class="text-left">{$projectGameTeamHome['name']}</td></tr>
    <tr><td class="text-left">{$projectGameTeamAway['name']}</td></tr>
  </table></td>
</tr>
EOD;
        }
        return $html;
    }
}
