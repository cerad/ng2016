<?php

namespace AppBundle\Action\Results\MedalRound;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;
use AppBundle\Action\Results\PoolPlay\Calculator\StandingsCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsMedalRoundView extends AbstractView
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    /** @var  StandingsCalculator */
    private $standingsCalculator;

    private $games;

    public function __construct(
        ScheduleRepository  $scheduleRepository,
        StandingsCalculator $standingsCalculator
    )
    {
        $this->scheduleRepository  = $scheduleRepository;
        $this->standingsCalculator = $standingsCalculator;
    }
    public function __invoke(Request $request)
    {
        $criteria = [];

        $this->project = $request->attributes->get('project');
        $criteria = $request->attributes->get('criteria');
                
        $this->games = count($criteria) > 1 ? $this->scheduleRepository->findProjectGames($criteria) : [];

        return new Response($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->renderPlayoffLinks()}

{$this->renderLegend()}
<!-- <hr style="border: 4px  ridge" /> -->
{$this->renderPlayoffs()}
<br />
</div>
EOD;
        $baseTemplate = $this->baseTemplate;
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderPlayoffLinks()
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
                unset($ages['U10']);  //remove U10
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
      <a href="{$this->generateUrl('app_results_medalround',$linkParams)}">{$div}</a>
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
  GS=Goals Scored, SP=Sportsmanship, YC=Caution, RC=Sendoff, TE=Total Ejections
  </h2>
</div>
<hr/>
EOD;

    }
    /* =======================================================================
     * The master playoffs section
     *
     */
    protected function renderPlayoffs()
    {
        $html = <<<EOD
<div id="layout-block">
<table class="results" border = "1">
EOD;

        $html .= $this->renderPlayoffGameHeader(current($this->games));
        foreach($this->games as $game) {
            $html .= $this->renderPlayoffGame($game);
        }

        $html .= <<<EOD
</tbody>
</table>
<br/>
</div>
EOD;
        return $html;
    }
    /* =============================================================
     * List the playoff games
     *
     */
    protected function renderPlayoffGameHeader($game)
    {   $html = <<<EOD
<thead>
<tr class="tbl-hdr">
    <th class="text-center" colspan="12">Medal Round Results :
EOD;
    $html .= str_replace('_', ' ', $game['level_key']);
    $html .= <<<EOD
    </th>
</tr>
<tr class="tbl-hdr">
  <th class="text-center">Game</th>
  <th class="text-center">Report</th>
  <th class="text-center">{$this->escape('Day & Time')}</th>
  <th class="text-center">Field</th>
  <th class="text-center">Pool</th>
  <th class="text-center">Home vs Away</th>
  <th class="text-center">GS</th>
  <th class="text-center">PE</th>
  <th class="text-center">SP</th>
  <th class="text-center">YC</th>
  <th class="text-center">RC</th>
  <th class="text-center">TE</th>
</tr>
</thead>
EOD;
        return $html;
    }
    protected function renderPlayoffGame($game)
    {

        $gameStart = sprintf('%s %8s',$game['dow'],$game['time']);

        $homeTeam = $game['teams'][1];
        $awayTeam = $game['teams'][2];

        $homeTeamReport = $homeTeam['report'];
        $awayTeamReport = $awayTeam['report'];

        $homeSportsmanship = isset($homeTeamReport['sportsmanship']) ? $homeTeamReport['sportsmanship'] : null;
        $awaySportsmanship = isset($awayTeamReport['sportsmanship']) ? $awayTeamReport['sportsmanship'] : null;

        $homePlayerWarnings = isset($homeTeamReport['playerWarnings']) ? $homeTeamReport['playerWarnings'] : null;
        $awayPlayerWarnings = isset($awayTeamReport['playerWarnings']) ? $awayTeamReport['playerWarnings'] : null;

        $homePlayerEjections = isset($homeTeamReport['playerEjections']) ? $homeTeamReport['playerEjections'] : null;
        $awayPlayerEjections = isset($awayTeamReport['playerEjections']) ? $awayTeamReport['playerEjections'] : null;

        $homeTotalEjections = $homePlayerEjections;
        $awayTotalEjections = $awayPlayerEjections;

        $gameNumber = $game['number'];
        $gameReportUpdateUrl = $this->generateUrl('game_report_update',['gameNumber' => $gameNumber]);

        return <<<EOD
<tr id="results-poolplay-games-{$gameNumber}" class="game-status-{$game['status']}">
  <td><a href="{$gameReportUpdateUrl}">{$gameNumber}</a></td>
  <td>{$game['report']['status']}</td>
  <td>{$gameStart}</td>
  <td><a href="">{$game['field_name']}</a></td>
  <td>{$homeTeam['group_slot']}<hr class="seperator"/>{$awayTeam['group_slot']}</td>
  <td class="text-left">{$this->escape($homeTeam['name'])}<hr class="seperator"/>{$this->escape($awayTeam['name'])}</td>
  <td>{$homeTeamReport['goalsScored']}<hr class="seperator"/>{$awayTeamReport['goalsScored']}</td>
  <td>{$homeTeamReport['pointsEarned']}<hr class="seperator"/>{$awayTeamReport['pointsEarned']}</td>
  <td>{$homeSportsmanship}<hr class="seperator"/>{$awaySportsmanship}</td>
  <td>{$homePlayerWarnings}<hr class="seperator"/>{$awayPlayerWarnings}</td>
  <td>{$homePlayerEjections}<hr class="seperator"/>{$awayPlayerEjections}</td>
  <td>{$homeTotalEjections}<hr class="seperator"/>{$awayTotalEjections}</td>
</tr>
EOD;

    }
}
