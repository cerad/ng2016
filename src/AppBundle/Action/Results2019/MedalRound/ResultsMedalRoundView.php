<?php

namespace AppBundle\Action\Results2019\MedalRound;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Results2019\ResultsGame;
use AppBundle\Action\Results2019\ResultsPool;
use Symfony\Component\HttpFoundation\Request;

class ResultsMedalRoundView extends AbstractView2
{
    private $searchForm;
    
    /** @var  ResultsPool[] */
    private $pools;

    public function __construct(
        ResultsMedalRoundSearchForm $searchForm
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
  <h2>GS=Goals Scored, YC=Caution, RC=Sendoff, TE=Total Ejections</h2>
</div>
<hr>
EOD;

    }
    /* =======================================================================
     * The master pool section
     *
     */
    private function renderPools()
    {
        if (count($this->pools) < 1) {
            return null;
        }
        $html = <<<EOD
<div id="layout-block">
<table class="results" border = "1">
EOD;

        // Pull the header info from the first pool
        $pool = array_values($this->pools)[0];
        $division = $pool->division;
        $program  = $pool->program;
        $html .= $this->renderGameHeader($program,$division);

        foreach($this->pools as $pool) {
            foreach($pool->getGames() as $game) {
                $html .= $this->renderGame($game);
            }
        }
        $html .= <<<EOD
</tbody>
</table>
<br/>
</div>
EOD;

        return $html;
    }
    private function renderGameHeader($program,$division)
    {
        $html = <<<EOD
<thead>
<tr class="tbl-hdr">
    <th class="text-center" colspan="12">Medal Round Results : {$division} {$program}</th>
</tr>
<tr class="tbl-hdr">
  <th class="text-center">Game</th>
  <th class="text-center">Report</th>
  <th class="text-center">{$this->escape('Day & Time')}</th>
  <th class="text-center">Field</th>
  <th class="text-center">Type</th>
  <th class="text-center">Slot</th>
  <th class="text-center">Home vs Away</th>
  <th class="text-center">GS</th>
  <th class="text-center">YC</th>
  <th class="text-center">RC</th>
  <th class="text-center">TE</th>
</tr>
</thead>
EOD;
        return $html;
    }
    protected function renderGame(ResultsGame $game)
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
  <td>{$homeTeam->poolTypeView} {$homeTeam->poolSlotView}</td>
  <td>{$homeTeam->poolTeamSlotView}{$hr}{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$this->escape($homeTeam->regTeamName)}{$hr}{$this->escape($awayTeam->regTeamName)}</td>
  <td>{$homeTeam->pointsScored} {$hr}{$awayTeam->pointsScored} </td>
  <td>{$homeTeamPlayerWarnings} {$hr}{$awayTeamPlayerWarnings} </td>
  <td>{$homeTeamPlayerEjections}{$hr}{$awayTeamPlayerEjections}</td>
  <td>{$homeTeamTotalEjections} {$hr}{$awayTeamTotalEjections} </td>
</tr>
EOD;

    }
}
