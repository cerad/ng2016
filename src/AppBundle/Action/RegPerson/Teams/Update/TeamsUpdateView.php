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
<legend>Notes on Adding a Team</legend>
<div class="app_help">
  <ul class="cerad-common-help">
    <ul class="ul_bullets">
          <li>Adding teams will cause games involving those teams to show up on your schedule.</li>
          <li>Adding teams will cause games involving those teams to be highlighted when requesting assignments.</li>
          <li>This can be handy to avoid conflicts when requesting games to officiate.</li>
        </ul>
    </ul>
</div>
EOD;

    }
}
