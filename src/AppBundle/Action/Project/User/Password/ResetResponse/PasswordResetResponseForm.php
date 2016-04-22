<?php
namespace AppBundle\Action\Project\User\Password\ResetResponse;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Project\User\ProjectUserRepository;

use Symfony\Component\HttpFoundation\Request;

class PasswordResetResponseForm extends AbstractForm
{
    /** @var  ProjectUserRepository */
    private $projectUserRepository;
    
    protected $formData = [
        'token'    => null,
        'password' => null,
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
        
        $token = filter_var(trim($data['token']), FILTER_SANITIZE_STRING);
        if (!$this->projectUserRepository->find($token)) {
            $errors['token'][] = [
                'name' => 'token',
                'msg'  => 'The token does not match any zAYSO accounts.'
            ];
        }
        $password = filter_var(trim($data['password']), FILTER_SANITIZE_STRING);
        if (strlen($password) < 3) {
            $errors['password'][] = [
                'name' => 'password',
                'msg'  => 'The password is too short.'
            ];
        }
        $this->formData = array_merge($this->formData,[
            'token'    => $token,
            'password' => $password,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" style="width: 400px;" action="{$this->generateUrl('user_password_reset_response')}" method="post">
  <div class="form-group">
    <label for="user_password_reset_response_token">zAYSO Password Reset Token</label>
    <input 
      type="text" id="user_password_reset_response" class="form-control" required
      name="token" value="{$this->escape($formData['token'])}" required placeholder="Password Reset Token" />
  </div>
  <div class="form-group">
    <label for="user_password_reset_response_password">New Password</label>
    <input 
      type="text" id="user_password_reset_response_password" class="form-control" required
      name="password" value="" required placeholder="****" />
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-plus"></span>
    <span>Reset My zAYSO Password</span>
  </button>
</form>
EOD;
        return $html;
    }
}