<?php

namespace AppBundle\Action;

use AppBundle\Action\AbstractTemplate;

class InstructionsView extends AbstractTemplate
{
    public function renderRefereeInstructions($url)
    {
        $html = 
<<<EOT
<legend>Instructions for Referees</legend>
<div class="app_help">
  <ul class="cerad-common-help">
    <ul class="ul_bullets">

    </ul>
  </ul>
<p>Detailed instructions for self-assigning are available <a href="{$url}" target="_blank">by clicking here</a>.</p>
</div>
<hr>
EOT;
        
        return $html;
    }
    public function renderAssignorInstructions($url)
    {
        return null;
    }
}
