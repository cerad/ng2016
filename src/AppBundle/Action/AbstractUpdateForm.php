<?php
namespace AppBundle\Action;

use AppBundle\Action\AbstractForm;

abstract class AbstractUpdateForm extends AbstractForm
{
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

        if (count($choices) > 1) { 
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
</select>
EOD;
        } else {
            $html = <<<EOD
EOD;
        }

        return $html;
    }
}
