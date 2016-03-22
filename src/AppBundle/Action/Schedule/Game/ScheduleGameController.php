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

    private $project;
    private $projectTeams = [];
    private $projectTeamKeys = [];

    private $projectGames = [];

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
            $search = array_merge($search,$session->get('schedule_game_search'));
        }
        // Search posted
        if ($request->isMethod('POST')) {
            $search = $request->request->all();
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
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderSearchForm(array $project, array $search)
    {
        $html = <<<EOD
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
  <tr></table>
  <div class="layout-block">
    <div class="controls"><button type="submit" id="form_search"" class="submit">Search</button></div>
    <div style="padding: 10px 0 0 25%; float: left;"><a href="/project/natgames/schedule-game.xls">Export to Excel</a></div>
    <div style="padding: 10px 0 0 4em; float: left;"><a href="/project/natgames/schedule-game.csv">Export to Text</a></div>
  </div>
</fieldset>
</form>
EOD;
        return $html;
    }
    protected function renderSearchCheckbox($name,$label,$items,$selected)
    {
        $items = array_merge(['All' => 'All'],$items);

        $html = <<<EOD
<table border="1">
  <tr><th colspan="30">{$label}</th></tr>
  <tr>
EOD;
        foreach($items as $value => $label) {
            $checked = in_array($value, $selected) ? ' checked' : null;
            $html .= <<<EOD
    <td align="center">{$label}<br />
    <input type="checkbox" name="{$name}[]" class="cerad-checkbox-all" value="{$value}"{$checked} /></td>
EOD;
        }
        $html .= "  </tr>\n</table>\n";

        return $html;
    }
    /* ============================================================
     * Render games
     */
    private function renderProjectGames()
    {
        $projectGameCount = count($this->projectGames);

        return <<<EOD
<div id="layout-block">
<div class="schedule-games-list">
<table id="schedule" class="schedule" border="1">
  <thead>
    <tr><th colspan="20" class="text-center">Team Schedule - Game Count: {$projectGameCount}</th></tr>
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
  {$this->renderProjectGamesRows()}
  </tbody>
</table>
</div>
<br />
</div
EOD;
    }
    private function renderProjectGamesRows()
    {
        $html = null;
        foreach($this->projectGames as $projectGame) {

            $projectGameTeamHome = $projectGame['project_game_teams'][1];
            $projectGameTeamAway = $projectGame['project_game_teams'][2];

            $html .= <<<EOD
<tr id="schedule-team-{$projectGame['number']}" class="game-status-{$projectGame['number']}">
  <td class="schedule-game" >{$projectGame['number']}</td>
  <td class="schedule-dow"  >{$projectGame['dow']}</td>
  <td class="schedule-time" >{$projectGame['time']}</td>
  <td class="schedule-field">{$projectGame['field_name']}</td>
  <td class="schedule-group">{$projectGame['group']}</td>
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
