<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Register;

use Zayso\Common\Contract\TemplateInterface;
use Zayso\Common\Traits\EscapeTrait;
use Zayso\Project\CurrentProject;

class RegisterTemplate implements TemplateInterface
{
    use EscapeTrait;

    private $form ;
    private $currentProject;

    public function __construct(
        CurrentProject $currentProject,
        RegisterForm   $form
    ) {
        $this->form = $form;
        $this->currentProject = $currentProject;
    }
    public function render()
    {
        $project = $this->currentProject;

        $content = <<<EOD
<legend>Register for {$this->escape($project->title)}</legend><br/>
{$this->form->render()}
EOD;
        return $project->pageTemplate->render($content);
    }
}