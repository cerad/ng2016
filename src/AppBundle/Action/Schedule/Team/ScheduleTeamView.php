<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleTeamView extends AbstractView
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    private $projectTeams    = [];
    private $projectTeamKeys = [];
    private $projectGames    = [];

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }
    public function __invoke(Request $request)
    {
        $this->projectTeamKeys = $request->attributes->get('projectTeamKeys');

        // Search teams
        $projectKey = $this->project['key'];
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
<div class="container">
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
          <div class="col-xs-10">
          <div class="row float-right">
      <button type="submit" id="form_search" name="search" class="btn btn-sm btn-primary submit">Search</button>
<a href="{$this->generateUrl('app_schedule_team',['_format' => 'xls'])}" class="btn btn-sm btn-primary">
  <span class="glyphicon glyphicon-share"></span> Export to Excel</a> 
<a href="{$this->generateUrl('app_schedule_team',['_format' => 'csv'])}" class="btn btn-sm btn-primary">
<span class="glyphicon glyphicon-share"></span> Export to Text</a> 
      </div>
      </div>
      <div class="clear-both"></div>
      <br/>
      <legend></legend>
  </fieldset>
  </form>
</div>
{$this->renderProjectGames()}
EOD;
        $baseTemplate = $this->baseTemplate;
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
<table id="schedule" class="schedule" border="0">
  <thead>
    <tr><th colspan="20" class="text-center">Team Schedule - Game Count: {$projectGameCount}</th></tr>
    <tr>
      <th class="schedule-game" >Game</th>
      <th class="schedule-dow"  >Day</th>
      <th class="schedule-time" >Time</th>
      <th class="schedule-field">Field</th>
      <th class="schedule-group">Group</th>
    <!--  <th class="schedule-blank">&nbsp;</th> -->
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
<!--  <td>&nbsp;</td>  -->
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
