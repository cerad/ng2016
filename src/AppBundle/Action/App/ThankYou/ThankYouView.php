<?php

namespace AppBundle\Action\App\ThankYou;

use AppBundle\Action\AbstractView2;

class ThankYouView extends AbstractView2
{
    public function render()
    {

        $project = $this->getCurrentProject()['info'];
        $title = $project['title'];
        $content = <<<EOD
<div id="layout-block">
<h1 class="text-center" style="padding-top:20px; font-style:italic; font-size:1.5em;"><emphasis>MAHALO for 
participating
 in 
the 
{$title}!</emphasis></h1>
</div>

EOD;

        $page = $this->renderBaseTemplate($content);

        return $this->newResponse($page);
    }
};