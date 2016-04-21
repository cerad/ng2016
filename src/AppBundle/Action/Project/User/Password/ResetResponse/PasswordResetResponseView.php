<?php
namespace AppBundle\Action\Project\User\Password\ResetResponse;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class PasswordResetResponseView extends AbstractView2
{
    /** @var  PasswordResetResponseForm */
    private $form;

    private $project;
    
    public function __construct(PasswordResetResponseForm $form)
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
<legend>Reset Password</legend>
{$this->form->render()}
{$this->renderHelp()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderHelp()
    {
        return <<<EOT
    <div class="app_help">
    <legend>Not received the token?</legend>
    <ul class="cerad-common-help">
        <li>
            Check your spam or junk mail folder.
        </li>
        <li>
            If you still need help, request support by <a href="{$this->project['support']['email']}?subject=Password%20Reset%20Help" target="_top">clicking here</a>.
        </li>
     </ul>
    </div>
EOT;
    }
}