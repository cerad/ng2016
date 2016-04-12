<?php
namespace AppBundle\Action\Project\User\Create;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class UserCreateForm extends AbstractForm
{
    /** @var Connection  */
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        $role = filter_var(trim($data['role']), FILTER_SANITIZE_STRING);

        $name = filter_var(trim($data['name']), FILTER_SANITIZE_STRING);
        if (strlen($name) === 0) {
            $errors['name'][] = [
                'name' => 'name',
                'msg'  => 'Name cannot be blank.'
            ];
        }

        $email  = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        $errors = $this->validateEmail($email,$errors);

        $password = filter_var(trim($data['password']), FILTER_SANITIZE_STRING);
        if (strlen($password) === 0) {
            $errors['password'][] = [
                'name' => 'password',
                'msg'  => 'Password cannot be blank.'
            ];
        }
        $this->formData = array_merge($this->formData,[
            'role'     => $role,
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);
        $this->formDataErrors = $errors;
    }
    private function validateEmail($email,$errors)
    {
        if (strlen($email) === 0) {
            $errors['email'][] = [
                'name' => 'email',
                'msg'  => 'Email cannot be blank.'
            ];
            return $errors;
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = [
                'name' => 'email',
                'msg'  => 'Email is not valid.'
            ];
            return $errors;
        }
        // Unique
        $sql = 'SELECT id FROM users WHERE email = ? OR username = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email,$email]);
        if ($stmt->fetch()) {
            $errors['email'][] = [
                'name' => 'email',
                'msg'  => 'Email is already being used.'
            ];
            return $errors;
        }
        return $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" style="width: 300px;" action="{$this->generateUrl('user_create')}" method="post" novalidate>
  <div class="form-group">
    <label for="user_create_name">Name</label>
    <input 
      type="text" id="user_create_name" class="form-control" required
      name="name" value="{$formData['name']}" required placeholder="Buffy Summers" />
  </div>
  <div class="form-group">
    <label for="user_create_email">Email</label>
    <input 
      type="email" id="user_create_email" class="form-control" required
      name="email" value="{$formData['email']}" placeholder="buffy@sunnydale.org" />
  </div>
  <div class="form-group">
    <label for="user_create_password">Password</label>
    <input 
      type="password" id="user_create_password" class="form-control" required
      name="password" value="" required placeholder="********" />
  </div>
  <div class="form-group">
    <label for="user_create_role">Role</label>
    <input 
      type="text" id="user_create_role" class="form-control" required
      name="role" value="{$formData['role']}" required placeholder="ROLE_..." />
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-plus"></span> Create New Zayso Account
  </button>
</form>

EOD;
        return $html;
    }
}