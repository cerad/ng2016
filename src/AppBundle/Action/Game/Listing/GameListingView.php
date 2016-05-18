<?php
namespace AppBundle\Action\Game\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Game\RegTeam;
use AppBundle\Action\Game\PoolTeam;

use Symfony\Component\HttpFoundation\Request;

class GameListingView extends AbstractView2
{
    /** @var  RegTeam[] */
    private $regTeams;

    /** @var  PoolTeam[] */
    private $poolTeams;

    private $searchForm;

    public function __construct(
        GameListingSearchForm $searchForm
    ) {
        $this->searchForm  = $searchForm;
    }
    public function __invoke(Request $request)
    {
        $this->regTeams  = $request->attributes->get('regTeams');
        $this->poolTeams = $request->attributes->get('poolTeams');

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
</div>
{$this->renderPoolTeams()}
<br/>
{$this->renderRegTeams()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    /* ========================================================================
     * Render Registered Teams
     *
     */
    protected function renderRegTeams()
    {
        if (!$this->regTeams) {
            return null;
        }
        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th colspan="20" class="text-center">Registered Teams</th></tr>
<tr class="tbl-hdr">
  <th class="text-center">Reg Team ID</th>
  <th class="text-center">Div</th>
  <th class="text-center">Team Key</th>
  <th class="text-center">Number</th>
  <th class="text-center">Team Name</th>
  <th class="text-center">Points</th>
  <th class="text-center">SAR</th>
  <th class="text-center">Pool Team 1</th>
  <th class="text-center">Pool Team 2</th>
  <th class="text-center">Pool Team 3</th>
  <th class="text-center">Pool Team 4</th>
</tr>
EOD;
        foreach($this->regTeams as $regTeam) {
            $html .= $this->renderRegTeam($regTeam);
        }
        $html .= <<<EOD
</table>
</div>
EOD;

        return $html;
    }
    protected function renderRegTeam(RegTeam $team)
    {
        return <<<EOD
<tr>
  <td class="text-left">  {$team->regTeamId} </td>
  <td class="text-center">{$team->division}  </td>
  <td class="text-left">  {$team->teamKey}   </td>
  <td class="text-left">  {$team->teamNumber}</td>
  <td class="text-left">  {$team->teamName}  </td>
  <td class="text-center">{$team->teamPoints}</td>
  <td class="text-center">{$team->orgView}   </td>
</tr>
EOD;
    }
    /* ========================================================================
     * Render Pool Teams
     *
     */
    protected function renderPoolTeams()
    {
        if (!$this->poolTeams) {
            return null;
        }
        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th colspan="20" class="text-center">Pool Teams</th></tr>
<tr class="tbl-hdr">
  <th class="text-center">Pool Team ID</th>
  <th class="text-center">Div</th>
  <th class="text-center">Keys</th>
  <th class="text-center">Views</th>
  <th class="text-center">Slots</th>
  <th class="text-center">Reg Team Key</th>
</tr>
EOD;
        foreach($this->poolTeams as $poolTeam) {
            $html .= $this->renderPoolTeam($poolTeam);
        }
        $html .= <<<EOD
</table>
</div>
EOD;

        return $html;
    }
    protected function renderPoolTeam(PoolTeam $team)
    {
        $regTeamIdParts = explode(':',$team->regTeamId);

        $regTeamKey = count($regTeamIdParts) === 2 ? $regTeamIdParts[1] : null;;

        return <<<EOD
<tr>
  <td class="text-left">  {$team->poolTeamId}</td>
  <td class="text-center">{$team->division}</td>
  <td class="text-left">  {$team->poolTypeKey} <br/>{$team->poolKey} <br/>{$team->poolTeamKey} </td>
  <td class="text-left">  {$team->poolTypeView}<br/>{$team->poolView}<br/>{$team->poolTeamView}</td>
  <td class="text-left">  &nbsp;<br/>{$team->poolSlotView}<br/>{$team->poolTeamSlotView}</td>
  <td class="text-center">{$regTeamKey}</td>
</tr>
EOD;
    }
}
