<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class PasswordResetRequestView extends AbstractView2
{
    /** @var  PasswordResetRequestForm */
    private $form;

    public function __construct(PasswordResetRequestForm $form)
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
<legend>Request Password Reset</legend>
{$this->form->render()}
EOD;
        return $this->renderBaseTemplate($content);
    }
}