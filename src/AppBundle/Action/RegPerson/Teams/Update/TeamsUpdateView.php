<?php
namespace AppBundle\Action\RegPerson\Teams\Update;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class TeamsUpdateView extends AbstractView2
{
    private $form;
    
    public function __construct(
        TeamsUpdateForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
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
    <th>Notes on Adding a Team</th>
  </thead>
  <tbody>
    <tr>
      <td width="15%">&nbsp;
        <ul>
          <li>Note: No real point in adding teams until we get the actual team names from national.</li>
          <li>Adding teams will cause games involving those teams to show up on your schedule.</li>
          <li>This can be handy when choosing which games to officiate.</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
</div>
EOD;

    }
}