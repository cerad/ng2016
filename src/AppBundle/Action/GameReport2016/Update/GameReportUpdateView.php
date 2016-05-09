<?php
namespace AppBundle\Action\GameReport2016\Update;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class GameReportUpdateView extends AbstractView2
{
    /** @var GameReportUpdateForm  */
    private $form;

    private $project;

    public function __construct(GameReportUpdateForm $form)
    {
        $this->form = $form;

    }
    public function __invoke(Request $request)
    {
        $this->project = $this->getCurrentProjectInfo();

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
{$this->form->render()}
<br />
{$this->renderScoringNotes()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderScoringNotes()
    {
        return <<<EOD
<legend class="text-left">Scoring Notes</legend>
<div class="app_table" id="notes">
<table>
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
        <ul>
          <li>Points earned will be calculated</li>
          <li>Win: 6 pts / Tie: 3 pts / Shutout: 1 pt</li>
          <li>For winner only: 1 pt per goal (3 pts max)
          <li>Player Cautions: No impact</li>
          <li>Player Sendoffs: -1 pt per sendoff</li>
          <li>Coach/Substitute/Spectator Ejections: -1 pt per ejection</li>
          <li>FORFEIT: Score as 1-0</li>
        </ul>
      </td>
      <td width="10%"></td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
      <td width="10%"></td>
      <td style="vertical-align: top;" width="35%" colspan=2>
        <ul class="ul_bullets">
          <li>For help with Match Reporting, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
          <li>For help with Schedule Management, contact {$this->project['schedules']['name']} at <a href="mailto:{$this->project['schedules']['email']}">{$this->project['schedules']['email']}</a> or at {$this->project['schedules']['phone']}</li>
          <li>For help with Account Management, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
</div>
EOD;

    }
}