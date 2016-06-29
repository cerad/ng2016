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
<legend>Notes on Adding a Person to your Crew</legend>
<div class="app_help">
  <ul class="cerad-common-help">
    <ul class="ul_bullets">
          <li>Adding a person to your crew allows you to sign them up for games.</li>
          <li>The person must have already created an account on zAYSO.</li>
          <li>Use Family for family members, Peer for others.</li>
          <li><strong>Please make sure the person knows you will be assigning their games.</strong></li>
        </ul>
</div>

EOD;

    }
}
