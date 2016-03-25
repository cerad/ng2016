<?php

namespace AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Schedule\ScheduleRepository;
use AppBundle\Action\Results\PoolPlay\Calculator\StandingsCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsPoolPlayController extends AbstractController
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    /** @var  StandingsCalculator */
    private $standingsCalculator;

    private $project;
    private $pools;

    public function __construct(
        ScheduleRepository  $scheduleRepository,
        StandingsCalculator $standingsCalculator
    )
    {
        $this->scheduleRepository  = $scheduleRepository;
        $this->standingsCalculator = $standingsCalculator;
    }
    protected function findProjectGames($params)
    {
    }
    public function __invoke(Request $request)
    {
        $this->project = $project = $this->getCurrentProject()['info'];

        $params = $request->query->all();

        $criteria = [];

        if (isset($params['project']) && $params['project']) {
            $criteria['projects'] = explode(',',$params['project']);
        }
        if (isset($params['programs']) && $params['programs']) {
            $criteria['programs'] = explode(',',$params['programs']);
        }
        if (isset($params['genders']) && $params['genders']) {
            $criteria['genders'] = explode(',',$params['genders']);
        }
        if (isset($params['ages']) && $params['ages']) {
            $criteria['ages'] = explode(',',$params['ages']);
        }
        if (isset($params['pools']) && $params['pools']) {
            $criteria['group_names'] = explode(',',$params['pools']);
        }
        $criteria['group_types'] = ['PP'];

        // Maybe pull criteria from session
        $games = count($criteria) > 1 ? $this->scheduleRepository->findProjectGames($criteria) : [];

        $this->pools = $this->standingsCalculator->generatePools(($games));

        return new Response($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->renderPoolLinks()}
<br />
{$this->renderLegend()}
<hr style="border: 4px  ridge" />
{$this->renderPools()}
<br />
</div>
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
  GS=Goals Scored, SP=Sportsmanship, YC=Caution, RC=Sendoff,
  <br/>
  TE=Total Ejections, PE=Points Earned, TPE=Points Earned, WP=Winning Percent
  <br/>
  GT=Games Total, GP=Games Played, GW=Games Won, GS=Goals Scored, GA=Goals Against, SF=SoccerFest
  </h2>
  </div>
</div>
EOD;

    }
    /* =======================================================================
     * The master pool section
     *
     */
    protected function renderPools()
    {
        $html = null;
        foreach($this->pools as $poolKey => $pool) {
            $html .= $this->renderPoolTeams($poolKey,$pool['teams']);
            $html .= $this->renderPoolGames($poolKey,$pool['games']);
        }
        return $html;
    }
    protected function renderPoolTeams($poolKey,$poolTeams)
    {
        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr class="tbl-title"><th colspan="16">Pool Team Standings : {$poolKey}</th></tr>
<tr class="tbl-hdr">
  <th>Pool Slot</th>
  <th>Team</th>
  <th align="center">TPE</th>
  <th align="center">WP</th>
  <th>&nbsp;</th>
  <th align="center">GT</th>
  <th align="center">GP</th>
  <th align="center">GW</th>
  <th align="center">GS</th>
  <th align="center">GA</th>
  <th align="center">YC</th>
  <th align="center">RC</th>
  <th align="center">TE</th>
  <th align="center">SP</th>
  <th align="center">SF</th>
</tr>
EOD;
        foreach($poolTeams as $poolTeamReport) {
            $html .= $this->renderPoolTeamReport($poolTeamReport);
        }
        $html .= <<<EOD
</table>
</div>
<br/>
EOD;

        return $html;
    }
    protected function renderPoolTeamReport($poolTeamReport)
    {
        $gameTeam = $poolTeamReport['team'];
        $totalEjections =
            $poolTeamReport['playerEjections'] +
            $poolTeamReport['coachEjections']  +
            $poolTeamReport['benchEjections']  +
            $poolTeamReport['specEjections'];
        return <<<EOD
<tr>
  <td>{$gameTeam['group_slot']}</td>
  <td class="text-left">{$gameTeam['name']}</td>
  <td align="center">{$poolTeamReport['pointsEarned']}</td>
  <td align="center">{$poolTeamReport['winPercent']}</td>
  <td>&nbsp;</td>
  <td align="center">{$poolTeamReport['gamesTotal']}</td>
  <td align="center">{$poolTeamReport['gamesPlayed']}</td>
  <td align="center">{$poolTeamReport['gamesWon']}</td>
  <td align="center">{$poolTeamReport['goalsScored']}</td>
  <td align="center">{$poolTeamReport['goalsAllowed']}</td>
  <td align="center">{$poolTeamReport['playerWarnings']}</td>
  <td align="center">{$poolTeamReport['playerEjections']}</td>
  <td align="center">{$totalEjections}</td>
  <td align="center">{$poolTeamReport['sportsmanship']}</td>
  <td align="center">{$gameTeam['points']}</td>
</tr>
EOD;
    }
    /* =============================================================
     * List the pool games
     *
     */
    protected function renderPoolGames($poolKey,$games)
    {
        $html = <<<EOD
<div id="layout-block">
<table class="results" border = "1">
<thead>
<tr class="tbl-title"><th colspan="16">Pool Games Results : {$poolKey}</th></tr>
<tr class="tbl-hdr">
  <th>Game</th>
  <th>Report</th>
  <th>{$this->escape('Day & Time')}</th>
  <th>Field</th>
  <th>Pool</th>
  <th>Home vs Away</th>
  <th>GS</th>
  <th>PE</th>
  <th>SP</th>
  <th>YC</th>
  <th>RC</th>
  <th>TE</th>
</tr>
</thead>
</tbody>
EOD;
        foreach($games as $game) {
            $html .= $this->renderPoolGame($game);
        }
        $html .= <<<EOD
</tbody>
</table>
</div>
EOD;
        return $html;
    }
    protected function renderPoolGame($game)
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

        return <<<EOD
<tr id="results-poolplay-games-{$game['number']}" class="game-status-{$game['status']}">
  <td><a href="">{$game['number']}</a></td>
  <td>REPORT</td>
  <td>{$gameStart}</td>
  <td><a href="">{$game['field_name']}</a></td>
  <td>{$homeTeam['group_slot']}<br/>{$awayTeam['group_slot']}</td>
  <td class="text-left">{$this->escape($homeTeam['name'])}<br/>{$this->escape($awayTeam['name'])}</td>
  <td>{$homeTeamReport['goalsScored']}<br/>{$awayTeamReport['goalsScored']}</td>
  <td>{$homeTeamReport['pointsEarned']}<br/>{$awayTeamReport['pointsEarned']}</td>
  <td>{$homeSportsmanship}<br/>{$awaySportsmanship}</td>
  <td>{$homePlayerWarnings}<br/>{$awayPlayerWarnings}</td>
  <td>{$homePlayerEjections}<br/>{$awayPlayerEjections}</td>
  <td>{$homeTotalEjections}<br/>{$awayTotalEjections}</td>
</tr>
EOD;

    }
}
