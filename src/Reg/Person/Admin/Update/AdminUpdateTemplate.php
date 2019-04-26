<?php
declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Update;

use Zayso\Common\Traits\EscapeTrait;
use Zayso\Reg\Person\RegPerson;

class AdminUpdateTemplate
{
    use EscapeTrait;

    public function render(RegPerson $person, AdminUpdateForm $updateForm) : string
    {
        $content = <<<EOD
<legend>Update Information for: {$this->escape($person->name)}</legend>
{$updateForm->render()}
EOD;
        return $content;
    }
}
