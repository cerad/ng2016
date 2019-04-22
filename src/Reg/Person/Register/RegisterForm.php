<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Register;


use Symfony\Component\HttpFoundation\Request;

use Zayso\Common\Contract\FormInterface;
use Zayso\Common\Traits\FormTrait;

class RegisterForm implements FormInterface
{
    use FormTrait;

    private $projectControls;
    private $formControls = [];

    public function __construct($projectControls, $formControls)
    {
        $this->projectControls = $projectControls;
        foreach($formControls as $key => $meta)
        {
            if (!isset($meta['type'])) {
                $meta = array_merge($meta,$projectControls[$key]);
            }
            $this->formControls[$key] = $meta;
        }
    }
    public function handleRequest(Request $request) : void
    {
        if (!$request->isMethod('POST')) return;
        $this->isPost = true;
        $data = $request->request->all();

        $this->submit = $data['register'];
        foreach($data as $key => $value)
        {
            // I think we have better filtering now
            if (!is_array($value)) {
                $value = filter_var(trim($value), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            }
            if (isset($this->formControls[$key])) {
                $meta = $this->formControls[$key];
                if (isset($meta['transformer'])) {
                    $transformer = $this->getTransformer($meta['transformer']);
                    if ($transformer) {
                        $value = $transformer->reverseTransform($value);
                    }
                }
            }
            $data[$key] = $value;
        }
        // Validate
        // $errors = [];
        //dump($data);
        unset($data['register']);
        unset($data['_csrf_token']);
        $this->setData($data);
        return;
    }
    public function render() : string
    {
        $csrfToken = 'TODO';
        $submitLabel = $this->formData['id'] ? 'Update Registration Information' : 'Submit Registration';
        $html = <<<EOD
{$this->renderFormErrors()}
<form 
  action="{$this->generateUrl('reg_person_register')}" method="post" 
  role="form" class="form-horizontal" novalidate>
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
        <span class="glyphicon glyphicon-edit"></span>
        <span>{$submitLabel}</span>
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
        if (isset($meta['transformer'])) {

            $transformer = $this->getTransformer($meta['transformer']);
            if ($transformer) {
                $value = $transformer->transform($value);
            }
            else dump($meta['transformer']);
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
    // TODO Merge this into the FormTrait
    private function renderFormControlInput($meta,$value,$id,$name)
    {
        $type = $meta['type'];
        switch($type) {
            case 'select':
                return $this->renderFormControlInputSelect($meta['choices'],$value,$id,$name);
            case 'textarea':
                return $this->renderFormControlInputTextArea($meta,$value,$id,$name);
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
  type="{$meta['type']}" id="{$id}" class="form-control" {$required}
  name="{$name}" value="{$value}" placeHolder="{$placeHolder}" />
EOD;
    }
    private function renderFormControlInputTextArea($meta,$value,$id,$name)
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;
        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;
        $rows = isset($meta['rows']) ? $meta['rows'] : 5;
        $value = $this->escape($value);
        return  <<<EOD
<textarea 
  id="{$id}" class="form-control" rows="{$rows}" {$required}
  name="{$name}" placeHolder="{$placeHolder}" >{$value}</textarea>
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
  <option value="{$choiceValue}" {$selected}>{$this->escape($choiceContent)}</option>
  
EOD;
        }
        $html .= <<<EOD
<select>
EOD;
        return $html;
    }
}
