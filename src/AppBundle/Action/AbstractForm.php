<?php
namespace AppBundle\Action;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractForm implements ContainerAwareInterface
{
    use AbstractActionTrait;
    
    protected $isPost = false;
    
    protected $submit;
    
    protected $formData       = [];
    protected $formDataErrors = [];
    
    public function setData($formData)
    {
        $this->formData = array_replace_recursive($this->formData, $formData);
    }
    public function getData()
    {
        return $this->formData;
    }
    public function isValid()
    {
        if (!$this->isPost) return false;
        if (count($this->formDataErrors)) return false;
        return true;
    }
    public function getSubmit() 
    {
        return $this->submit;
    }
    /** 
     * @param string $id
     * @return DataTransformerInterface
     */
    protected function getTransformer($id)
    {
        return $this->container->get($id);
    }
    abstract function handleRequest(Request $request);
    
    abstract public function render();

    protected function renderFormErrors()
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
    /* ===========================================================================
     * Your basic select input element
     * 
     */
    protected function renderInputSelect($choices,$value,$name,$id=null,$size=null)
    {
        $id = $id ? : $name;
        
        $size = $size ? sprintf(' size="%d"',$size) : null;

        $multiple = is_array($value) ? ' multiple' : null;

        $values   = is_array($value) ? $value : [$value];

        $html = <<<EOD
<select id="{$id}" name="{$name}"{$multiple}{$size} class="form-control">
EOD;
        foreach($choices as $choiceValue => $choiceContent)
        {
            $selected = in_array($choiceValue,$values) ? ' selected' : null;;
            $choiceValue   = $this->escape($choiceValue);
            $choiceContent = $this->escape($choiceContent);
            $html .= <<<EOD
  <option value="{$choiceValue}"{$selected}>{$choiceContent}</option>
  
EOD;
        }
        $html .= <<<EOD
<select>
EOD;
        return $html;
    }
    /* ===================================================================================
     * Row of check boxes with label
     * No id for now, not sure they make sense here
     */
    protected function renderInputSearchCheckbox($choices,$values,$name,$label)
    {
        $style = 'text-align: center';

        $html = <<<EOD
<table>
  <tr><th colspan="30" style="{$style}">{$label}</th></tr>
    <td style="{$style}">All<br />
    <input type="checkbox" name="{$name}" class="cerad-checkbox-all" value="All" /></td>
EOD;
        foreach($choices as $choiceValue => $choiceContent) {
            $checked = in_array($choiceValue, $values) ? ' checked' : null;
            $choiceValue   = $this->escape($choiceValue);
            $choiceContent = $this->escape($choiceContent);
            $html .= <<<EOD
    <td style="{$style}">{$choiceContent}<br />
    <input type="checkbox" name="{$name}" value="{$choiceValue}"{$checked} /></td>
EOD;
        }
        $html .= <<<EOD
  </tr>
</table>
</div>
EOD;
        return $html;
    }
    /* ======================================================================
     * Filter / sanitize inputs
     * Setting the integer flag will return an integer
     */
    protected function filterScalar($data,$name,$integer=false)
    {
        $itemData = isset($data[$name]) ? $data[$name] : null;
        $filter = $integer ? FILTER_SANITIZE_NUMBER_INT : FILTER_SANITIZE_STRING;
        $itemData = filter_var(trim($itemData), $filter);
        return $integer ? (integer)$itemData : $itemData;
    }
    // Could these two be combined?
    protected function filterArray($data,$name,$integer=false)
    {
        $itemData = isset($data[$name]) ? $data[$name] : [];
        $filter = $integer ? FILTER_SANITIZE_NUMBER_INT : FILTER_SANITIZE_STRING;
        $items = [];
        foreach($itemData as $item) {
            $item = filter_var(trim($item),$filter);
            if ($item) {
                $items[] = $integer ? (integer)$item : $item;
            }
        }
        return $items;
    }
}