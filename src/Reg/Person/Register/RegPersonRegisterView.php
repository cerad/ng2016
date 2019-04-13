<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Register;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class RegPersonRegisterView extends AbstractView2
{
    private $form ;

    public function __construct(RegPersonRegisterForm $form)
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