<?php
namespace AppBundle\Action\Game\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\RegTeam;
use AppBundle\Action\Game\PoolTeam;

use Symfony\Component\HttpFoundation\Request;

class GameListingView extends AbstractView2
{
    /** @var  RegTeam[] */
    private $regTeams;

    /** @var  PoolTeam[] */
    private $poolTeams;

    /** @var  Game[] */
    private $games;

    private $searchForm;

    public function __construct(
        GameListingSearchForm $searchForm
    ) {
        $this->searchForm  = $searchForm;
    }
    public function __invoke(Request $request)
    {
        $this->games     = $request->attributes->get('games');
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
<br/>
{$this->renderGames()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    /* ========================================================================
     * Render Games
     *
    */
    protected function renderGames()
    {
        if (!$this->games) {
            return null;
        }
        $gameCount = count($this->games);

        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th colspan="20" class="text-center">Games: {$gameCount}</th></tr>
<tr class="tbl-hdr">
  <th class="text-center">Game</th>
  <th class="text-center">Day</th>
  <th class="text-center">Time</th>
  <th class="text-center">Field</th>
  <th class="text-center">Group</th>
  <th class="text-center">Slot</th>
  <th class="text-center">Home / Away</th>
</tr>
EOD;
        foreach($this->games as $game) {
            $html .= $this->renderGame($game);
        }
        $html .= <<<EOD
</table>
</div>
EOD;

        return $html;
    }
    protected function renderGame(Game $game)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        $trId = 'game-' . $game->gameId;

        return <<<EOD
<tr id="{$trId}" class="game-status-{$game->status}">
  <td class="text-left">  {$game->gameNumber}</td>
  <td class="text-left">  {$game->dow}       </td>
  <td class="text-left">  {$game->time}      </td>
  <td class="text-center">{$game->fieldName} </td>
  <td class="text-center">{$game->poolView}  </td>
  <td>{$homeTeam->poolTeamSlotView}<hr class="separator">{$awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$homeTeam->regTeamName}<hr class="separator">{$awayTeam->regTeamName}</td>
</tr>
EOD;
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
        $regTeamCount = count($this->regTeams);

        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th colspan="20" class="text-center">Registered Teams: {$regTeamCount}</th></tr>
<tr class="tbl-hdr">
  <th class="text-center">Reg Team Key</th>
  <th class="text-center">Number</th>
  <th class="text-center">Team Name</th>
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
        $poolKeys = array_replace(['&nbsp;','&nbsp;','&nbsp;','&nbsp;'],$team->poolKeys);
        return <<<EOD
<tr>
  <td class="text-left">  {$team->teamKey}   </td>
  <td class="text-left">  {$team->teamNumber}</td>
  <td class="text-left">  {$team->teamName}  </td>
  <td class="text-center">{$team->orgView}   </td>
  <td class="text-center">{$poolKeys[0]}     </td>
  <td class="text-center">{$poolKeys[1]}     </td>
  <td class="text-center">{$poolKeys[2]}     </td>
  <td class="text-center">{$poolKeys[3]}     </td>
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
        $poolTeamCount = count($this->poolTeams);

        $html = <<<EOD
<div id="layout-block">
<table class="standings" border = "1">
<tr><th colspan="20" class="text-center">Pool Teams: {$poolTeamCount}</th></tr>
<tr class="tbl-hdr">
  <th class="text-center">Pool Keys</th>
  <th class="text-center">Pool Views</th>
  <th class="text-center">Slots</th>
  <th class="text-center">Reg Team Key</th>
  <th class="text-center">Soccerfest<br/>Points</th>
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
  <td class="text-left">  {$team->poolTypeKey} <br/>{$team->poolKey} <br/>{$team->poolTeamKey} </td>
  <td class="text-left">  {$team->poolTypeView}<br/>{$team->poolView}<br/>{$team->poolTeamView}</td>
  <td class="text-left">  &nbsp;<br/>{$team->poolSlotView}<br/>{$team->poolTeamSlotView}</td>
  <td class="text-center">{$regTeamKey}</td>
  <td class="text-center">{$team->extraPoints}</td>
</tr>
EOD;
    }
}