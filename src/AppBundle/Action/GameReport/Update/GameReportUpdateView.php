<?php
namespace AppBundle\Action\GameReport\Update;

use AppBundle\Action\AbstractTemplate;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameReportUpdateView extends AbstractTemplate
{
    private $gameStatuses;
    private $reportStatuses;

    public function __construct()
    {
        $this->gameStatuses = [
            'Normal'            => 'Normal',
            'InProgress'        => 'In Progress',
            'Played'            => 'Played',
            'ForfeitByHomeTeam' => 'Forfeit By Home Team',
            'ForfeitByAwayTeam' => 'Forfeit By Away Team',
            'Cancelled'         => 'Cancelled',
            'Suspended'         => 'Suspended',
            'Terminated'        => 'Terminated',
            'StormedOut'        => 'Stormed Out',
            'HeatedOut'         => 'Heated Out',
        ];
        $this->reportStatuses = [
            'Pending'   => 'Pending',
            'Submitted' => 'Submitted',
            'Verified'  => 'Verified',
            'Clear'     => 'Clear',
        ];
    }
    public function __invoke(Request $request)
    {
        $gameReport = $request->attributes->get('gameReport');

        $content = <<<EOD
{$this->renderForm($gameReport)}
<br />
{$this->renderScoringNotes()}
EOD;
        $this->baseTemplate->setContent($content);

        return new Response($this->baseTemplate->render());
    }
    protected function renderForm($gameReport)
    {
        $game = $gameReport['game'];

        $gameNumber     = $game['number'];
        $gameNumberNext = $gameNumber + 1;

        $gameReportUpdateUrl = $this->generateUrl('game_report_update',['gameNumber' => $gameNumber]);

        $homeTeamReport = $gameReport['teamReports'][1];
        $awayTeamReport = $gameReport['teamReports'][2];

        $homeTeam = $homeTeamReport['team'];
        $awayTeam = $awayTeamReport['team'];

        $homeTeamReportPrefix = 'gameReport[teamReports][1]';
        $awayTeamReportPrefix = 'gameReport[teamReports][2]';
        
        $html = <<<EOD
<form method="post" action="{$gameReportUpdateUrl}" class="cerad_common_form1">
<h2>{$this->escape($gameReport['desc'])}</h2>
<table class="scoring" border="1" style="width: 70%;min-width: 660px">
<tbody>
<tr>
  <td style="width:25%;min-width:160">&nbsp;</td>
  <td style="width:25%">Home : {$homeTeam['groupSlot']}<br />{$homeTeam['name']}</td>
  <td style="width:25%">Away : {$awayTeam['groupSlot']}<br />{$awayTeam['name']}</td>
</tr><tr>
  <td style="text-align: right;">Goals Scored</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[goalsScored]" size="4" value="{$homeTeamReport['goalsScored']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[goalsScored]" size="4" value="{$awayTeamReport['goalsScored']}" /></td>
</tr><tr>
  <td style="text-align: right;">Sportsmanship</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[sportsmanship]" size="4" value="{$homeTeamReport['sportsmanship']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[sportsmanship]" size="4" value="{$awayTeamReport['sportsmanship']}" /></td>
</tr>
<tr><td colspan="5">&nbsp;</td></tr>
<tr>
  <td style="text-align: right;">Player Cautions</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[playerWarnings]" size="4" value="{$homeTeamReport['playerWarnings']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[playerWarnings]" size="4" value="{$awayTeamReport['playerWarnings']}" /></td>
</tr><tr>
  <td style="text-align: right;">Player Sendoffs</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[playerEjections]" size="4" value="{$homeTeamReport['playerEjections']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[playerEjections]" size="4" value="{$awayTeamReport['playerEjections']}" /></td>
</tr>
<tr><td colspan="5">&nbsp;</td></tr>
<tr>
  <td style="text-align: right;">Coach Ejections</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[coachEjections]" size="4" value="{$homeTeamReport['coachEjections']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[coachEjections]" size="4" value="{$awayTeamReport['coachEjections']}" /></td>
</tr><tr>
  <td style="text-align: right;">Substitute Ejections</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[benchEjections]" size="4" value="{$homeTeamReport['benchEjections']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[benchEjections]" size="4" value="{$awayTeamReport['benchEjections']}" /></td>
</tr><tr>
  <td style="text-align: right;">Spectator Ejections</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[specEjections]" size="4" value="{$homeTeamReport['specEjections']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[specEjections]" size="4" value="{$awayTeamReport['specEjections']}" /></td>
</tr>
<tr><td colspan="5">&nbsp;</td></tr>
<tr>
  <td style="text-align: right;">Serious Injuries</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[injuries]" size="4" value="{$homeTeamReport['injuries']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[injuries]" size="4" value="{$awayTeamReport['injuries']}" /></td>
</tr>
<tr><td colspan="5">&nbsp;</td></tr>
<tr>
  <td style="text-align: right;vertical-align: text-top">Notes</td>
  <td colspan="2" style="padding-left: 5px; text-align: left;">
    <textarea name="gameReport[notes]" rows="4" cols="42" wrap="hard" class="textarea">{$this->escape($gameReport['notes'])}</textarea>
  </td>
</tr>
<tr><td colspan="4">&nbsp;</td></tr>
<tr>
  <td style="text-align: right;">Points Earned</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[pointsEarned]" readonly="readonly" size="4" value="{$homeTeamReport['pointsEarned']}" /></td>
  <td><input type="number" name="{$awayTeamReportPrefix}[pointsEarned]" readonly="readonly" size="4" value="{$awayTeamReport['pointsEarned']}" /></td>
</tr><tr>
  <td style="text-align: right;">Points Minus</td>
  <td><input type="number" name="{$homeTeamReportPrefix}[pointsMinus]" readonly="readonly" size="4" value="{$homeTeamReport['pointsMinus']}" /></td>
  <td><input type="number" name="{$homeTeamReportPrefix}[pointsMinus]" readonly="readonly" size="4" value="{$awayTeamReport['pointsMinus']}" /></td>
</tr>
</tbody>
</table>
<br />
<table style="width:80%">
<tr>
  <td style="min-width:275px;">&nbsp;</td>
  <td style="min-width:275px;">&nbsp;</td>
  <td style="min-width:100px;">
    <button type="submit" name="save" class="submit">Save</button>
    <button type="submit" name="next" class="submit">Save Then Next</button>
    <input type="number"  name="nextGameNumber" value="{$gameNumberNext}" />
  </td>
  <td style="min-width:225px;vertical-align:top;"><a href="/project/natgames/results-poolplay?level=AYSO_U12B_Core&amp;pool=D#results-poolplay-games-11210">Return to Schedule</a></td>
</tr>
</table>
<hr>
<table style="width:80%">
<tr>
  <td style="min-width:275px;">&nbsp;</td>
  <td style="min-width:275px;">
    <div>
    <label for="gameStatus">Game Status</label>
    <select id="gameStatus" name="gameReport[game][status]">
EOD;
        $status = $game['status'];
        foreach($this->gameStatuses as $value => $text) {
            $selected = $status == $value ? ' selected' : null;
            $html .= <<<EOD
      <option{$selected} value="{$value}">{$text}</option>
EOD;
        }
        $html .= <<<EOD
      </select>
    </div>
  <td>
  <td style="min-width:210px;">
    <div><label for="form_gameReport_status">Report Status</label>
    <select id="gameReportStatus" name="gameReport[status]">
EOD;
        $status = $gameReport['status'];
        foreach($this->reportStatuses as $value => $text) {
            $selected = $status == $value ? ' selected' : null;
            $html .= <<<EOD
      <option{$selected} value="{$value}">{$text}</option>
EOD;
        }
        $html .= <<<EOD
    </select>
    </div>
  </td>
  <td style="min-width:275px;">&nbsp;</td>
</tr>
</table>
</form>
EOD;
        return $html;
    }
    /* ====================================================
     * The help section
     */
    protected function renderScoringNotes()
    {
        return <<<EOD
<div class="app_table" id="notes">
<table>
  <thead>
    <th colspan="4">Scoring Notes</th>
  </thead>
  <tbody>
    <tr>
      <td width="10%"></td>
      <td style="vertical-align: top;" width="35%">
        <ul>
          <li>Enter score and other info then click "Save"</li>
          <li>Status fields will update themselves</li>
          <br><br>
          <li><strong>NOTE:</strong> Six points for proper participation in Soccerfest are added separately</li>
        </ul>
      </td>
      <td width="35%">
          <p>Points earned will be calculated</p>
        <ul>
          <li>Win: 6 pts / Tie: 3 pts / Shutout: 1 pt</li>
          <li>For winner only: 1 pt per goal (3 pts max)
          <li>Player Cautions: No impact</li>
          <li>Player Sendoffs: -1 pt per sendoff</li>
          <li>Coach/Substitute Ejections: -1 pt per ejection</li>
          <li>FORFEIT: Score as 1-0</li>
        </ul>
      </td>
      <td width="10%"></td>
    </tr>
    <tr></tr>
    <tr>
      <td width="10%"></td>
      <td style="vertical-align: top;" width="35%" colspan=2>
        <ul class="cerad-common-help">
        <ul class="ul_bullets">
          <li>For help with Match Reporting, contact Art Hundiak at <a href="mailto:ahundiak@gmail.com">ahundiak@gmail.com</a> or at 256-457-5943</li>
          <li>For help with Schedule Management, contact Bill Owen at <a href="mailto:stats@ayso13.org">stats@ayso13.org</a> or at 626-484-5439</li>
          <li>For help with Account Management, contact Art Hundiak at <a href="mailto:ahundiak@gmail.com">ahundiak@gmail.com</a> or at 256-457-5943</li>
        </ul></ul>
      </td>
    </tr>
  </tbody>
</table>
</div>
EOD;

    }
}