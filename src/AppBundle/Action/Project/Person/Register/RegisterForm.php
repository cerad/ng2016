<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class RegisterForm extends AbstractForm
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
        $qb = $this->conn->createQueryBuilder();
        $qb->addSelect([
            'user.id AS id',
        ]);
        $qb->from('users','user');
        $qb->andWhere('user.email = :email OR user.username = :email');
        $qb->setParameter('email',$email);
        $row = $qb->execute()->fetch();
        if ($row) {
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
<form action="{$this->generateUrl('user_create')}" method="post" novalidate>
<div class="col-xs-3">
  <div class="row">
    <label for="user_create_name">Name</label>
    <input type="text" id="user_create_name" name="name" value="{$formData['name']}" required placeholder="Buffy Summers" /><br />
  </div>
  <div class="row">
    <label for="user_create_email">Email</label>
    <input type="email" id="user_create_email" name="email" value="{$formData['email']}" required placeholder="buffy@sunnydale.org" /><br />
  </div>
  <div class="row">
    <label for="user_create_password">Password:</label>
    <input type="password" id="user_create_password" name="password" value="" required placeholder="********" /><br />
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <div class="row">
    <button type="submit" class="btn btn-sm btn-primary submit">
      <span class="glyphicon glyphicon-edit"></span>Create Zayso Account
    </button>
  </div>
</div>
</form>
EOD;
        return $html;
    }
}