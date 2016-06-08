<?php
namespace AppBundle\Action\RegPerson\Persons\Update;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class PersonsUpdateView extends AbstractView2
{
    private $form;
    
    public function __construct(
        PersonsUpdateForm $form)
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
    <th>Notes on Adding a Person to Your Crew</th>
  </thead>
  <tbody>
    <tr>
      <td width="15%">&nbsp;
        <ul>
          <li>Adding a person to your crew allows you to sign them up for games.</li>
          <li>The person must have already created an account on zAYSO.</li>
          <li>Use Family for family members, Peer for others.</li>
          <li><strong>Please make sure the person knows you will be assigning their games.</strong></li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
</div>

EOD;

    }
}