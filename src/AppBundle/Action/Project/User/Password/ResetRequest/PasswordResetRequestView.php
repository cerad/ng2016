<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetRequestView extends AbstractView
{
    /** @var  PasswordResetRequestForm */
    private $form;

    public function __construct(PasswordResetRequestForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
        return new Response($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<h3>Request Password Reset</h3>
{$this->form->render()}
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();

    }
}