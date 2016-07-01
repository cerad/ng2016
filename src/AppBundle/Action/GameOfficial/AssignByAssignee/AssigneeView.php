<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class AssigneeView extends AbstractView2
{
    /** @var AssigneeForm  */
    private $form;

    private $project;

    public function __construct(AssigneeForm $form)
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
<div class="cerad-common-help">
<legend>Notes on Referee Self-Assignment Procedure</legend>
    <ul class="ul_bullets">
          <li>Use the drop down to select "Request Assignment"</li>
          <li>Click Submit</li>
          <li>A "Conflicts With" message indicates you tried to double book yourself</li>
          <li>Click "Return to Schedule" and you will see your name listed for this game</li>
          <li>The Assignor will be notified and will approve your request</li>
          <li>You may, before the assignor approves your request, rescind your request by returning to this game, selecting "Remove Me From Assignment" and clicking Submit.</li>
          <li>After the assignor has approved the assignment, you may request to be removed from the match by returning to this game, selecting "Request Turnback of Assignment" and clicking Submit.</li>
          <li>The Assignor will be notified and, depending on whether a replacement can be assigned, may or may not approve the request.</li>
          <li><strong>NOTE: You are obliged to keep the assignment until the Assignor releases you.</strong></li>
    </ul>
</div>
EOD;

    }
}
