<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignor;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class AssignorView extends AbstractView2
{
    private $form;

    private $project;

    public function __construct(AssignorForm $form)
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
{$this->renderNotes()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderNotes()
    {
        return <<<EOD
<div class="app_table" id="notes">
<table class="app_help">
  <thead>
    <th>Notes on Assignor Procedures</th>
  </thead>
  <tbody>
    <tr>
      <td width="15%">&nbsp;
        <ul>
          <li>All registered officials will be vetted for current registration and Safe Haven</li>
          <li>2 July Soccerfest and Pool Play games are open-assignment:
          <ul>
            <li>Referees get to pick their own matches as individuals or as teams</li>
            <li>Assignors responsibilities:
            <ul>
              <li>Approve requests and manage turnbacks</li>
              <li>If Referees self-assignments seem odd (e.g., under certified for division)
              <ul>
                <li>Consult with Site Referee Admin</li>
                <li>Contact Referee to clarify</li>
                <li>If necessary, reassign the match to another (more qualified) Referee</li>
              </ul>
              </li>
              <li>Ensure all matches have 3 Referees coverage</li>
              <li>Contact referees by phone to fill open slots</li>
            </li>
          </ul>
       </ul>
          </li>
              <li>QF, SF, FM, CM on Sat & Sun are assigned by the Assignor</li>
              <li>Use pool play to gather information about the Referees and, with the Referee Administrator, agree on assignments.</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
</div>

EOD;

    }
}