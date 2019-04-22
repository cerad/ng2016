<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

use Symfony\Component\Form\DataTransformerInterface;
use Zayso\Common\Locator\DataTransformerLocator;

trait FormTrait
{
    use EscapeTrait;
    use RouterTrait;

    /** @var DataTransformerLocator */
    protected $transformerLocator;

    protected $isPost = false;

    protected $submit;

    protected $formData       = [];
    protected $formDataErrors = [];
    protected $formDataMessages = [];

    /** @required */
    public function injectTransformerLocator(DataTransformerLocator $transformerLocator)
    {
        $this->transformerLocator = $this->transformerLocator ?: $transformerLocator;
    }
    public function setData(array $formData) : void
    {
        $this->formData = array_replace_recursive($this->formData, $formData);
    }
    public function getData() : array
    {
        return $this->formData;
    }
    public function isValid() : bool
    {
        if (!$this->isPost) return false;
        if (count($this->formDataErrors)) return false;
        return true;
    }
    public function getSubmit() : string
    {
        return $this->submit;
    }
    /**
     * @param string $id
     * @return DataTransformerInterface
     * Either transformer locator or just project for this
     */
    protected function getTransformer($id) : ?DataTransformerInterface
    {
        return $this->transformerLocator->has($id) ? $this->transformerLocator->get($id) : null;
    }


    protected function renderFormMessages() : string
    {
        $msgs = $this->formDataMessages;

        if (count($msgs) === 0) return '';

        $html = '<hr><legend>Entry Success Messages</legend><div class="messages" ><ul>' . "\n";
        foreach($msgs as $name => $items) {
            foreach($items as $item) {
                $html .= <<<EOD
<li>{$item['msg']}</li>
EOD;
            }}
        $html .= '</ul></div>' . "\n";
        return $html;
    }

    protected function renderFormErrors() : string
    {
        $errors = $this->formDataErrors;

        if (count($errors) === 0) return '';

        $html = '<hr><legend>Entry Error Messages</legend><div class="errors" ><ul>' . "\n";
        foreach($errors as $name => $items) {
            foreach($items as $item) {
                $html .= <<<EOD
<li>{$item['msg']}</li>
EOD;
            }}
        $html .= '</ul></div>' . "\n";
        return $html;
    }
    /* ===========================================================================
     * Your basic select input element
     *
     */
    protected function renderInputSelect($choices,$value,$name,$id=null,$size=null,$class="form-control")
    {
        $id = $id ? : $name;

        $size = $size ? sprintf(' size="%d"',$size) : null;

        $multiple = is_array($value) ? ' multiple' : null;

        $values   = is_array($value) ? $value : [$value];

        $html = <<<EOD
<select id="{$id}" name="{$name}" {$multiple}{$size} class="{$class}">
EOD;
        foreach($choices as $choiceValue => $choiceContent)
        {
            $selected = in_array($choiceValue,$values) ? ' selected' : null;
            $choiceValue   = $this->escape($choiceValue);
            $choiceContent = $this->escape($choiceContent);
            $html .= <<<EOD
  <option value="{$choiceValue}" {$selected}>{$choiceContent}</option>

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
    <input type="checkbox" name="{$name}" value="{$choiceValue}" {$checked} /></td>
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
     */
    protected function filterScalarString($data,$name) : ?string
    {
        $itemData = isset($data[$name]) ? $data[$name] : null;
        $itemData = filter_var(trim($itemData), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
        if ($itemData === null || strlen($itemData) < 1) {
            return null;
        }
        return $itemData;
    }
    protected function filterScalarInteger($data,$name) : ?int
    {
        $itemData = isset($data[$name]) ? $data[$name] : null;
        $itemData = filter_var(trim($itemData), FILTER_SANITIZE_NUMBER_INT );
        if ($itemData === null || strlen($itemData) < 1) {
            return null;
        }
        return (int)$itemData;
    }
    // Could these two be combined? TODO Handle nulls better
    protected function filterArray($data,$name,$integer=false)
    {
        $itemData = isset($data[$name]) ? $data[$name] : [];
        $filter = $integer ? FILTER_SANITIZE_NUMBER_INT : FILTER_SANITIZE_STRING;
        $items = [];
        foreach($itemData as $item) {
            $item = filter_var(trim($item),$filter);
            if ($item) {
                $items[] = $integer ? (int)$item : $item;
            }
        }
        return $items;
    }
}
