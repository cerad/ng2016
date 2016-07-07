<?php

namespace AppBundle\Action\Results2016\Sportsmanship;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Results2016\ResultsSportsmanshipCalculator;

use Symfony\Component\HttpFoundation\Request;

class ResultsSportsmanshipView extends AbstractView2
{
    private $searchForm;
    
    private $sportsmanshipCalculator;

    /** @var  ResultsPool[] */
    private $pools;

    public function __construct(
        ResultsSportsmanshipSearchForm $searchForm,
        ResultsSportsmanshipCalculator $sportsmanshipCalculator
    )
    {
        $this->searchForm  = $searchForm;
        
        $this->sportsmanshipCalculator = $sportsmanshipCalculator;
    }
    public function __invoke(Request $request)
    {
        $this->pools = $request->attributes->get('pools');

        return $this->newResponse($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->searchForm->render()}
{$this->renderLegend()}
{$this->renderSportsmanship()}
</div>
EOD;

        return $this->renderBaseTemplate($content);
    }
    /* =======================================================================
     * Render the legend
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
     * The master pool section
     *
     */
    protected function renderSportsmanship()
    {
        if (empty($this->pools)) {
            return null;
        }
        
        $calculator = $this->sportsmanshipCalculator;
        
        $html = null;
        //foreach($this->pools as $pool) {
        //    $poolView = $pool->poolView;
        //    $html .= $this->renderPoolTeams($poolView,$pool->getPoolTeamStandings());
        //    $html .= $this->renderPoolGames($poolView,$pool->getGames());
        //}
        foreach($this->pools as $pool) {
            $games[] = $pool->getGames();
        }

        $divisionView = array_values($this->pools)[0]->division;

        $html = <<<EOD
<table class="standings" border = "1">
EOD;
    
        $html .= $this->renderSportsmanshipHeader($divisionView);            

        $html .= $this->renderSportsmanshipStandings($calculator->getSportsmanshipStandings($games));
        $html .=  <<<EOD
</tbody>
</table>
<br/>
</div>
EOD;

        return $html;
    }
    protected function renderSportsmanshipHeader($division)
    {
        $divisionTitle = str_replace('B',' Boys',$division);
        $divisionTitle = str_replace('G',' Girls',$divisionTitle);

        $html = <<<EOD
<tr><th class="text-center" colspan="4" style="font-size: 1.2em;">Sportsmanship Standings : {$divisionTitle}</th></tr>
<tr class="tbl-hdr">
    <th>&nbsp;Team</th>
    <th class="text-center">Sportsmanship</th>
    <th class="text-center">Avg SP</th>
    <th class="text-center">Games Played</th>
</tr>
EOD;
        return $html;        
    }
    protected function renderSportsmanshipStandings($poolTeams)
    {
        $html = null;
        //$teamTotalSportsmanship = $team['totalSP'];
        //$avgSportsmanship = $team['avgSP'];
        //$teamGamesPlayed = $team['gamesPlayed'];
        //

        foreach($poolTeams as $regTeamName => $team) {
            $html .= <<<EOD
<tr>
  <td class="text-left">  {$regTeamName}</td>
  <td class="text-center">{$team['totalSP']}</td>
  <td class="text-center">{$team['avgSP']}</td>
  <td class="text-center">{$team['gamesPlayed']}</td>
</tr>
EOD;
        }

        return $html;

    }
}
