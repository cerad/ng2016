<?php

namespace AppBundle\Action\Results2016\PoolPlay;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Results2016\ResultsGame;
use AppBundle\Action\Results2016\ResultsPool;
use AppBundle\Action\Results2016\ResultsPoolTeam;
use Symfony\Component\HttpFoundation\Request;

class ResultsPoolPlayView extends AbstractView2
{
    private $searchForm;
    
    /** @var  ResultsPool[] */
    private $pools;

    public function __construct(
        ResultsPoolPlaySearchForm $searchForm
    )
    {
        $this->searchForm  = $searchForm;
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
{$this->renderPools()}
</div>
EOD;
        return $this->renderBaseTemplate($content);
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
  GT=Games Total, GP=Games Played, GW=Games Won, GS=Goals Scored, GA=Goals Allowed, SF=SoccerFest
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
        foreach($this->pools as $pool) {
            $poolView = $pool->poolView;
            $html .= $this->renderPoolTeams($poolView,$pool->getPoolTeamStandings());
            $html .= $this->renderPoolGames($poolView,$pool->getGames());
        }
        return $html;
    }

    /**
     * @param  string $poolView
     * @param  ResultsPoolTeam[] $poolTeams
     * @return string
     */
    protected function renderPoolTeams($poolView,$poolTeams)
    {
        $html = <<<EOD
<div id="layout-block">
<legend class="float-right">Pool Team Standings : {$poolView}</legend>

<table class="standings" border = "1">
<tr class="tbl-hdr">
  <th class="text-center">Pool Slot</th>
  <th class="text-center">Team</th>
  <th class="text-center">TPE</th>
  <th class="text-center">WP</th>
  <th class="text-center">GT</th>
  <th class="text-center">GP</th>
  <th class="text-center">GW</th>
  <th class="text-center">GS</th>
  <th class="text-center">GA</th>
  <th class="text-center">YC</th>
  <th class="text-center">RC</th>
  <th class="text-center">TE</th>
  <th class="text-center">SP</th>
  <th class="text-center">SF</th>
</tr>
EOD;
        foreach($poolTeams as $poolTeam) {
            $html .= $this->renderPoolTeam($poolTeam);
        }
        $html .= <<<EOD
</table>
</div>

EOD;

        return $html;
    }
    protected function renderPoolTeam(ResultsPoolTeam $poolTeam)
    {
        return <<<EOD
<tr>
  <td>{$poolTeam->poolTeamSlotView}</td>
  <td class="text-left">  {$poolTeam->regTeamName}</td>
  <td class="text-center">{$poolTeam->pointsEarned}</td>
  <td class="text-center">{$poolTeam->winPercentView}</td>
  <td class="text-center">{$poolTeam->gamesTotal}</td>
  <td class="text-center">{$poolTeam->gamesPlayed}</td>
  <td class="text-center">{$poolTeam->gamesWon}</td>
  <td class="text-center">{$poolTeam->pointsScored}</td>
  <td class="text-center">{$poolTeam->pointsAllowed}</td>
  <td class="text-center">{$poolTeam->playerWarnings}</td>
  <td class="text-center">{$poolTeam->playerEjections}</td>
  <td class="text-center">{$poolTeam->totalEjections}</td>
  <td class="text-center">{$poolTeam->sportsmanship}</td>
  <td class="text-center">{$poolTeam->regTeamPoints}</td>
</tr>
EOD;
    }
    /* =============================================================
     * List the pool games
     *
     */
    protected function renderPoolGames($poolView,$games)
    {
        $html = <<<EOD
<div id="layout-block">
<table class="results" border = "1">
<thead>
<!-- <tr class="tbl-title"><th colspan="16">Pool Games Results : {$poolView}</th></tr> -->
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
</tbody>
EOD;
        foreach($games as $game) {
            $html .= $this->renderPoolGame($game);
        }
        $html .= <<<EOD
</tbody>
</table>
<br/>
</div>
EOD;
        return $html;
    }
    protected function renderPoolGame(ResultsGame $game)
    {
        $gameStart = sprintf('%s %8s',$game->dow,$game->time);

        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        // TODO Investigate layout quirk, maybe a min height or something
        $space = '&nbsp;';
        $homeTeamPlayerWarnings  = $homeTeam->playerWarnings  ? : $space;
        $awayTeamPlayerWarnings  = $awayTeam->playerWarnings  ? : $space;
        $homeTeamPlayerEjections = $homeTeam->playerEjections ? : $space;
        $awayTeamPlayerEjections = $awayTeam->playerEjections ? : $space;
        $homeTeamTotalEjections  = $homeTeam->totalEjections  ? : $space;
        $awayTeamTotalEjections  = $awayTeam->totalEjections  ? : $space;

        $gameReportUpdateUrl = $this->generateUrl('game_report_update',[
            'projectId'  => $game->projectId,
            'gameNumber' => $game->gameNumber,
            'back'       => $this->getCurrentRouteName(),
        ]);
        $hr = '<hr class="separator"/>';

        return <<<EOD
<tr id="game-{$game->gameId}" class="game-status-{$game->status}">
  <td><a href="{$gameReportUpdateUrl}">{$game->gameNumber}</a></td>
  <td>{$game->reportState}</td>
  <td>{$gameStart}</td>
  <td><a href="">{$game->fieldName}</a></td>
  <td>{$homeTeam->poolTeamSlotView}{$hr}{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$this->escape($homeTeam->regTeamName)}{$hr}{$this->escape($awayTeam->regTeamName)}</td>
  <td>{$homeTeam->pointsScored} {$hr}{$awayTeam->pointsScored} </td>
  <td>{$homeTeam->pointsEarned} {$hr}{$awayTeam->pointsEarned} </td>
  <td>{$homeTeam->sportsmanship}{$hr}{$awayTeam->sportsmanship}</td>
  <td>{$homeTeamPlayerWarnings} {$hr}{$awayTeamPlayerWarnings} </td>
  <td>{$homeTeamPlayerEjections}{$hr}{$awayTeamPlayerEjections}</td>
  <td>{$homeTeamTotalEjections} {$hr}{$awayTeamTotalEjections} </td>
</tr>
EOD;

    }
}
