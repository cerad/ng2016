<?php declare(strict_types=1);

namespace Zayso\Main\Admin;

use Zayso\Common\Traits\AuthorizationTrait;
use Zayso\Common\Traits\FormTrait;

use Zayso\Project\ProjectInterface;
use Zayso\Reg\Person\RegPersonFinder;

use Symfony\Component\HttpFoundation\Request;

class AdminSwitchUserForm
{
    use FormTrait;
    use AuthorizationTrait;

    /** @var ProjectInterface */
    private $project;

    private $regPersonFinder;
    
    public function __construct(
        RegPersonFinder $regPersonFinder
    ) {
        $this->regPersonFinder = $regPersonFinder;
    }
    // TODO Might be better to have explicit injectProject to cover setData stuff
    public function handleRequest(Request $request, ProjectInterface $project = null)
    {
        $this->project = $project;

        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();

        $this->formData['username'] = $this->filterScalarString($data,'username');

        $this->formDataErrors = $errors;
    }

    // TODO: Maybe add project selection?
    public function render()
    {
        if (!$this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return null;
        }
        $projectId = $this->project->projectId;

        $userChoices = array_merge(
            [null => 'Switch To User'],
            $this->regPersonFinder->findUserChoices($projectId)
        );

        $html = <<<EOD
<form method="post" action="{$this->generateUrl('app_admin')}" class="form-inline" role="form">
  <div class="form-group col-xs-12">
      <label class="form-label" for="username">User</label>
      {$this->renderInputSelect($userChoices,null,'username','username',null)}
  <button type="submit" class="btn btn-sm btn-primary">Switch To User</button>
  </div>
{$this->renderFormErrors()}
</form>
<br>
<br>
EOD;
        return $html;
    }
}
