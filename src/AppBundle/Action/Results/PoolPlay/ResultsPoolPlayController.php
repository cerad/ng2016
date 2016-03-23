<?php

namespace AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsPoolPlayController extends AbstractController
{
    /** @var  ScheduleRepository */
    protected $scheduleRepository;

    protected $project;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }
    public function __invoke(Request $request)
    {
        $this->project = $project = $this->getCurrentProject()['info'];

        $params = $request->query->all();
        print_r($params);

        /*
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
        */
        return new Response($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
{$this->renderPoolLinks()}
<br />
<br />
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderPoolLinks()
    {
        $projectKey     = $this->project['key'];
        $poolChoices    = $this->project['choices']['pools'];
        $genderChoices  = $this->project['choices']['genders'];
        $programChoices = $this->project['choices']['programs'];

        $html = null;

        // Add Table for each program
        foreach($poolChoices as $programKey => $genders) {

            $programLabel = $programChoices[$programKey];

            $html .= <<<EOD
<table>
  <tr>
    <td class="row-hdr" rowspan="2" style="border: 1px solid black;">{$programLabel}</td>
EOD;
            // Add columns for each gender
            foreach($genders as $genderKey => $ages) {

                $genderLabel = $genderChoices[$genderKey];

                $html .= <<<EOD
    <td class="row-hdr" style="border: 1px solid black;">{$genderLabel}</td>
EOD;
                // Add column for division
                foreach($ages as $age => $poolNames) {
                    $div = $age . $genderKey;
                    $linkParams = [
                        //'div'     => $div,
                        'project'  => $projectKey,
                        'ages'     => $age,
                        'genders'  => $genderKey,
                        'programs' => $programKey
                    ];
                    $html .= <<<EOD
    <td style="border: 1px solid black;">
      <a href="{$this->generateUrl('app_results_poolplay',$linkParams)}">{$div}</a>
EOD;
                    // Add link for each pool
                    foreach($poolNames as $poolName)
                    {
                        $linkParams['pools'] = $poolName;
                        //$linkParams['pool'] = [$poolName,'X','Y','Z'];

                        $html .= <<<EOD
      <a href="{$this->generateUrl('app_results_poolplay',$linkParams)}">{$poolName}</a>
EOD;
                    }
                    // Finish division column
                    $html .= <<<EOD
    </td>
EOD;

                }
                // Force a row shift foreach gender column
                $html .= <<<EOD
    </tr>
EOD;
            }
            // Finish the program table
            $html .= <<<EOD
  </tr>
</table>
<br />
EOD;
        }
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

            $projectGameTeamHome = $projectGame['project_game_teams'][1];
            $projectGameTeamAway = $projectGame['project_game_teams'][2];

            $html .= <<<EOD
<tr id="schedule-{$projectGame['number']}" class="game-status-{$projectGame['number']}">
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
