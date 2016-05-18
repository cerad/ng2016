<?php
namespace AppBundle\Action\Game\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Game\RegTeam;
use Symfony\Component\HttpFoundation\Request;

class GameListingView extends AbstractView2
{
    /** @var  RegTeam[] */
    private $regTeams;

    private $searchForm;

    public function __construct(
        GameListingSearchForm $searchForm
    ) {
        $this->searchForm  = $searchForm;
    }
    public function __invoke(Request $request)
    {
        $this->regTeams = $request->attributes->get('regTeams');

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
{$this->renderRegTeams()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    protected function renderRegTeams()
    {
        if (!$this->regTeams) {
            return null;
        }
        $html = <<<EOD
<div id="layout-block">
<legend class="float-right">Registered Teams</legend>

<table class="standings" border = "1">
<tr class="tbl-hdr">
  <th class="text-center">Team ID</th>
  <th class="text-center">Div</th>
  <th class="text-center">Team Key</th>
  <th class="text-center">Number</th>
  <th class="text-center">Team Name</th>
  <th class="text-center">Points</th>
  <th class="text-center">SAR</th>
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
}
