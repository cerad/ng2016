<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetRequestView extends AbstractView
{
    /** @var  form */
    private $form;

    public function __invoke(Request $request)
    {
        $this->form = $request->attributes->get('form');

        return new Response($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<h3>Reset Password</h3>
{$this->form->render()}
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();

    }
}