<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleTeamController extends AbstractController
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
        $this->project = $this->getCurrentProject();

        // Save selected teams in session
        $session = $request->getSession();
        $this->projectTeamKeys = $session->has('project_team_keys') ? $session->get('project_team_keys') : [];

        // Search posted
        if ($request->isMethod('POST')) {
            $this->projectTeamKeys = $request->request->get('project_teams');
            $session->set('project_team_keys',$this->projectTeamKeys);
        }

        // Search teams
        $projectKey = $this->project['info']['key'];
        $this->projectTeams = $this->scheduleRepository->findProjectTeams($projectKey);

        // Find games
        $this->projectGames = $this->scheduleRepository->findProjectGamesForProjectTeamKeys($this->projectTeamKeys);

        return new Response($this->renderPage());
    }
    private function renderPage()
    {
        $projectTeamCount = count($this->projectTeams);

        /** =================
         * Note: Using a table here because at one point we supported multiple programs
         */

        $content = <<<EOD
<div id="layout-block">
  <form class="cerad_common_form1" method="post" action="{$this->generateUrl('app_schedule_team')}">
  <fieldset>
    <table>
      <tr><th>Core Teams({$projectTeamCount})</th></tr>
      <tr><td>
        <select name="project_teams[]" multiple size="10">
          <option value=0">Select Teams</option>
          {$this->renderProjectTeamOptions()}
        </select>
      <td><tr>
    </table>
    <div class="layout-block">
      <div class="controls"><button type="submit" id="form_search" name="search" class="submit">Search</button></div>
      <div style="padding: 10px 0 0 25%; float: left;"><a href="/project/natgames/schedule-team.xls">Export to Excel</a></div>
      <div style="padding: 10px 0 0 4em; float: left;"><a href="/project/natgames/schedule-team.csv">Export to Text</a></div>
    </div>
  </fieldset>
  </form>
</div>
{$this->renderProjectGames()}
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    private function renderProjectTeamOptions()
    {
        $html = null;
        foreach($this->projectTeams as $projectTeam) {

            $selected = in_array($projectTeam['key'],$this->projectTeamKeys) ? ' selected' : null;

            $html .= sprintf('<option%s value="%s">%s %s</option>' . "\n",
                $selected,
                $this->escape($projectTeam['key']),
                $this->escape($projectTeam['div']),
                $this->escape($projectTeam['name']));
        }
        return $html;
    }
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

            $projectGameTeamHome = $projectGame['teams'][1];
            $projectGameTeamAway = $projectGame['teams'][2];

            $html .= <<<EOD
<tr id="schedule-team-{$projectGame['number']}" class="game-status-{$projectGame['number']}">
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
