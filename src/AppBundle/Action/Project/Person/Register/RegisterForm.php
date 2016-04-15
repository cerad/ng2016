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
    private $projectControls;
    private $formControls;

    public function __construct(Connection $conn, $projectControls, $formControls)
    {
        $this->conn = $conn;
        $this->projectPlans    = $projectControls;
        $this->projectControls = $projectControls;

        $this->formControls = $formControls;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;

        $data = $request->request->all();

        // Validate
        $errors = [];

        //dump($data);
        unset($data['register']);
        unset($data['_csrf_token']);

        $this->formData = array_replace_recursive($this->formData,$data);

        if (1) return;

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
  {$this->renderFormControls()}
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
    private function renderFormControls()
    {
        $html = null;
        foreach($this->formControls as $key => $meta)
        {
            if (!isset($meta['type'])) {
                $meta = array_merge($meta,$this->projectControls[$key]);
            }
            $html .= $this->renderFormControl($key,$meta);
        }
        return $html;
    }
    private function renderFormControl($key,$meta)
    {
        $group = isset($meta['group']) ? $meta['group'] : null;

        $id   = $group ? sprintf('%s_%s', $group,$key) : $key;
        $name = $group ? sprintf('%s[%s]',$group,$key) : $key;

        $default = isset($meta['default']) ? $meta['default'] : null;

        if ($group) {
            $value = isset($this->formData[$group][$key]) ? $this->formData[$group][$key] : $default;
        }
        else {
            $value = isset($this->formData[$key]) ? $this->formData[$key] : $default;
        }
        $label = isset($meta['label']) ? $this->escape($meta['label']) : null;

        return <<<EOD
  <div class="form-group">
    <label class="control-label col-sm-4" for="{$id}">{$label}</label>
    <div class="col-sm-8">
      {$this->renderFormControlInput($meta,$value,$id,$name)}
    </div>
  </div>
EOD;
    }
    private function renderFormControlInput($meta,$value,$id,$name)
    {
        $type = $meta['type'];

        switch($type) {
            case 'select': return $this->renderFormControlInputSelect($meta['choices'],$value,$id,$name);
        }
        return $this->renderFormControlInputText($meta,$value,$id,$name);
    }
    private function renderFormControlInputText($meta,$value,$id,$name)
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $value = $this->escape($value);

        return  <<<EOD
<input 
  type="{$meta['type']} id="{$id}" class="form-control" {$required}
  name="{$name}" value="{$value}" placeHolder="{$placeHolder}"} />
EOD;
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