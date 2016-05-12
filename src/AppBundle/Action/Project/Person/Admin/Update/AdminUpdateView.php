<?php
namespace AppBundle\Action\Project\Person\Admin\Update;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class AdminUpdateView extends AbstractView2
{
    private $updateForm;
    private $displayKey;

    /** @var  ProjectPerson[] */
    private $projectPerson;

    private $projectPersonViewDecorator;

    public function __construct(AdminUpdateForm $updateForm)
    {
        $this->updateForm = $updateForm;
    }
    public function __invoke(Request $request)
    {
        $this->projectPerson = $request->attributes->get('projectPerson');

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = null;
        
        $name = $this->projectPerson->name;
        
        $content .= <<<EOD
<legend>Update Information for: {$name}</legend>
{$this->updateForm->render()}
EOD;
                
        return $this->renderBaseTemplate($content);
    }

}
