<?php
namespace AppBundle\Action\Project\User\Create;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCreateForm
{
    /** @var  RouterInterface */
    private $router;

    private $isPost = false;
    
    private $formData;
    private $formDataErrors = [];

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    public function setData($formData)
    {
        $this->formData = $formData;
    }
    public function getData()
    {
        return $this->formData;
    }
    
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
    public function isValid()
    {
        if (!$this->isPost) return false;
        if (count($this->formDataErrors)) return false;
        return true;
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
    private function renderFormErrors()
    {
        $errors = $this->formDataErrors;

        if (count($errors) === 0) return null;

        $html = '<div class="errors" style="color: #0000FF">' . "\n";
        foreach($errors as $name => $items) {
            foreach($items as $item) {
                $html .= <<<EOD
<div>{$item['msg']}</div>
EOD;
            }}
        $html .= '</div>' . "\n";
        return $html;
    }
}