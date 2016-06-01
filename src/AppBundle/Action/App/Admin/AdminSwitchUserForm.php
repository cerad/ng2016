<?php
namespace AppBundle\Action\App\Admin;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\RegPerson\RegPersonFinder;

use Symfony\Component\HttpFoundation\Request;

class AdminSwitchUserForm extends AbstractForm
{
    private $regPersonFinder;
    
    public function __construct(
        RegPersonFinder $regPersonFinder
    ) {
        $this->regPersonFinder = $regPersonFinder;
    }
    public function handleRequest(Request $request)
    {
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
        $projectId = $this->getCurrentProjectKey();

        $userChoices = array_merge(
            [null => 'Switch To User'],
            $this->regPersonFinder->findUserChoices($projectId)
        );

        $html = <<<EOD
<form method="post" action="{$this->generateUrl('app_admin')}" class="form-inline role="form"">
  <div class="form-group">
      <label for="username">User</label>
      {$this->renderInputSelect($userChoices,null,'username','username')}
  </div>
  <button type="submit" class="btn btn-default">Switch To User</button>
{$this->renderFormErrors()}
</form>

EOD;
        return $html;
    }
}
