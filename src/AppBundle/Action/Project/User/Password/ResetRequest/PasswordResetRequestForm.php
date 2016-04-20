<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Project\User\ProjectUserRepository;
use Symfony\Component\HttpFoundation\Request;

class PasswordResetRequestForm extends AbstractForm
{
    /** @var  ProjectUserRepository */
    private $projectUserRepository;
    
    protected $formData = [
        'identifier' => null,        
    ];
    public function __construct(
        ProjectUserRepository $projectUserRepository
    )
    {
        $this->projectUserRepository = $projectUserRepository;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];
        
        $identifier = filter_var(trim($data['identifier']), FILTER_SANITIZE_STRING);

        if (!$this->projectUserRepository->find($identifier)) {
            $errors['identifier'][] = [
                'name' => 'identifier',
                'msg' => 'The email does not match any Zayso accounts.'
            ];

        }
        $this->formData = array_merge($this->formData,[
            'identifier' => $identifier,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;
        
        $identifier = $formData['identifier'] ? : $this->getAuthenticationUtils()->getLastUsername();
        
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" style="width: 300px;" action="{$this->generateUrl('user_password_reset_request')}" method="post">
  <div class="form-group">
    <label for="user_password_reset_request_identifier">Zayso Email</label>
    <input 
      type="text" id="user_password_reset_request" class="form-control" required
      name="identifier" value="{$this->escape($identifier)}" required placeholder="Zayso Email" />
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-plus"></span>
    <span>Reset My Zayso Password</span>
  </button>
</form>
EOD;
        return $html;
    }
}