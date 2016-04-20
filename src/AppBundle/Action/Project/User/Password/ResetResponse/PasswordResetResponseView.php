<?php
namespace AppBundle\Action\Project\User\Password\ResetResponse;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class PasswordResetResponseView extends AbstractView2
{
    /** @var  PasswordResetResponseForm */
    private $form;

    public function __construct(PasswordResetResponseForm $form)
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
<legend>Reset Password</legend>
{$this->form->render()}
EOD;
        return $this->renderBaseTemplate($content);
    }
}