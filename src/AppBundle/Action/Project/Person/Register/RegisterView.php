<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class RegisterView extends AbstractView2
{
    /** @var  RegisterForm */
    private $form ;

    public function __construct(RegisterForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
        return $this->newResponse($this->render());
    }
    private function render()
    {
        $project = $this->getCurrentProjectInfo();

        $content = <<<EOD
<legend>Register for {$this->escape($project['title'])}</legend><br/>
{$this->form->render()}
EOD;
        return $this->renderBaseTemplate($content);
    }
}