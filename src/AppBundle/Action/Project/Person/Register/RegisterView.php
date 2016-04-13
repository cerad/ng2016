<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegisterView extends AbstractView
{
    /** @var  RegisterForm */
    private $registerForm ;

    private $projectPerson;

    public function __invoke(Request $request)
    {
        $this->projectPerson = $request->attributes->get('projectPerson');
        $this->registerForm  = $request->attributes->get('registerForm');

        return new Response($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<h3>Register for {$this->project['title']}</h3><br/>
{$this->registerForm->render()}
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
}