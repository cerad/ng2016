<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class RegisterForm extends AbstractForm
{
    /** @var Connection  */
    private $conn;
    private $projectPlans;

    public function __construct(Connection $conn, $projectPlans)
    {
        $this->conn = $conn;
        $this->projectPlans = $projectPlans;
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
        $formData = array_merge($formData,[
            'fedKey' => null,
        ]);
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form 
  action="{$this->generateUrl('project_person_register')}" method="post" 
  role="form" class="form-horizontal" style="width: 500px;" novalidate
>
  <div class="form-group"> 
    <div class="col-sm-offset-4 col-sm-8">
      <button type="submit" name="register" value="nope" class="btn btn-sm btn-primary">
        <span class="glyphicon glyphicon-edit"></span>No Thanks, Just Spectating
      </button>
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-4" for="name">Registration Name:</label>
    <div class="col-sm-8">
      <input 
        type="text" id="email" class="form-control" required 
        name="name" value="{$this->escape($formData['name'])}" placeholder="Your Name">
    </div>
  </div>
   <div class="form-group">
    <label class="control-label col-sm-4" for="email">Registration Email:</label>
    <div class="col-sm-8">
      <input 
        type="email" id="email" class="form-control" required 
        name="email" value="{$this->escape($formData['email'])}" placeholder="Your Email">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-4" for="fedKey"><a href="">eAYSO Volunteer ID:</a></label>
    <div class="col-sm-8">
      <input 
        type="integer" id="fedKey" class="form-control" 
        name="fedKey" value="{$this->escape($formData['fedKey'])}" placeholder="8-digit number">
    </div>
  </div>
  {$this->renderProjectPlans()}
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <div class="form-group"> 
    <div class="col-sm-offset-4 col-sm-8">
      <button type="submit" name="register" value="register" class="btn btn-sm btn-primary">
        <span class="glyphicon glyphicon-edit"></span>Register
      </button>
    </div>
  </div>
</form>
EOD;
        return $html;
    }
    private function renderProjectPlans()
    {
        $html = null;
        foreach($this->projectPlans as $key => $plan) {
            $html .= $this->renderFormControl('plans',$key,$plan);
        }
        return $html;
    }
    private function renderFormControl($group,$key,$data)
    {
        $id   = $group ? sprintf('%s_%s', $group,$key) : $key;
        $name = $group ? sprintf('%s[%s]',$group,$key) : $key;

        if ($group) {
            $value = isset($this->formData[$group][$key]) ? $this->formData[$group][$key] : $data['default'];
        }
        else {
            $value = isset($this->formData[$key]) ? $this->formData[$key] : $data['default'];
        }

        return <<<EOD
  <div class="form-group">
    <label class="control-label col-sm-4" for="{$id}">{$this->escape($data['label'])}</label>
    <div class="col-sm-8">
      {$this->renderFormControlInput($data,$value,$id,$name)}
    </div>
  </div>
EOD;
    }
    private function renderFormControlInput($data,$value,$id,$name)
    {
        $type = $data['type'];

        switch($type) {
            case 'select': return $this->renderFormControlInputSelect($data['choices'],$value,$id,$name);
        }
        return sprintf("<h1>Unknown input type %s</h1>\n",$type);
    }
    private function renderFormControlInputSelect($choices,$value,$id,$name)
    {
        $html = <<<EOD
<select id="{$id}" name="{$name}" class="form-control">
EOD;
        foreach($choices as $choiceValue => $choiceContent)
        {
            $selected = ($value === $choiceValue) ? ' selected' : null;
            $html .= <<<EOD
  <option value="{$choiceValue}"{$selected}>{$this->escape($choiceContent)}</option>
EOD;
        }
        $html .= <<<EOD
<select>
EOD;
        return $html;
    }
}