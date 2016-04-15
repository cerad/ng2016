<?php

namespace AppBundle\Action\Results\Sportsmanship;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;
use AppBundle\Action\Results\Sportsmanship\Calculator\SportsmanshipCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsSportsmanshipView extends AbstractView
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    /** @var  SportsmanshipCalculator */
    private $sportsmanshipCalculator;

    private $games;
    private $standings;

    public function __construct(
        ScheduleRepository  $scheduleRepository,
        SportsmanshipCalculator $sportsmanshipCalculator
    )
    {
        $this->scheduleRepository  = $scheduleRepository;
        $this->sportsmanshipCalculator = $sportsmanshipCalculator;
    }
    public function __invoke(Request $request)
    {
        $criteria = [];

        $this->project = $request->attributes->get('project');
        $criteria = $request->attributes->get('criteria');
          
        $this->games = count($criteria) > 1 ? $this->scheduleRepository->findProjectGames($criteria) : [];
 
        $this->standings = $this->sportsmanshipCalculator->getSportsmanshipStandings($this->games);
        
        return new Response($this->renderPage());
    }
    
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->renderSportsmanshipLinks()}

{$this->renderLegend()}

<br />
{$this->renderSportsmanship()}
</div>
EOD;
        $baseTemplate = $this->baseTemplate;
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderSportsmanshipLinks()
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
      <a href="{$this->generateUrl('app_results_sportsmanship',$linkParams)}">{$div}</a>
EOD;
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

EOD;
        }
        return $html;
    }
    /* =======================================================================
     * Render the legend
     * TODO: add some javascript for hiding it or maybe a checkbox
     *       and possibly add hints
     */
    public function renderLegend()
    {
        return <<<EOD
<div class="results-legend">
  <h2>
  <p>Sportsmanship=Total Sportsmanship Points</p>
  <p>Avg SP=Total Points/Games Played</p>
  <br/>
  <p><em>NOTE:</em> Forfeiting teams receive 0 for sportsmanship and the game is included in the average.</p>
<p>A forfeited game is not counted in the average for the team that did not forfeit.</p>
  <br/>
<p>In each age division, the team that earns the highest average points per game over their pool play games will be honored for outstanding sportsmanship</p>
<p>and all team members and coaches of those teams will receive medals.&nbsp;&nbsp;In the event of a tie, all team members and coaches will receive medals.</p>
  </h2>
  </div>
</div>
<hr/>
EOD;

    }
    /* =======================================================================
     * The master playoffs section
     *
     */
    /* =======================================================================
     * The master pool section
     *
     */
    protected function renderSportsmanship()
    {
        if (empty($this->games)) {
            return null;
        }
        
        $levelKey = str_replace('_',' ',array_values($this->games)[0]['level_key']);

        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th class="text-center" colspan="4" style="font-size: 1.2em;">Sportsmanship Standings: {$levelKey}</th></tr>
<tr class="tbl-hdr">
    <th>&nbsp;Team</th>
    <th class="text-center">Sportsmanship</th>
    <th class="text-center">Avg SP</th>
    <th class="text-center">Games Played</th>
</tr>
EOD;
        foreach($this->standings as $name=>$team) {
            $html .= $this->renderSportsmanshipStandings($name, $team);
        }

        $html .= <<<EOD
</tbody>
</table>
<br/>
</div>
EOD;
        return $html;
    }

    protected function renderSportsmanshipStandings($name, $team)
    {
        $teamTotalSportsmanship = $team['totalSP'];
        $avgSportsmanship = $team['avgSP'];
        $teamGamesPlayed = $team['gamesPlayed'];

        $html =  <<<EOD
<tr>
    <td class="text-left">&nbsp;{$name}</td>
    <td class="text-center">{$teamTotalSportsmanship}</td>
    <td class="text-center">{$avgSportsmanship}</td>
    <td class="text-center">{$teamGamesPlayed}</td>
</tr>
EOD;
        
        return $html;

    }
}
