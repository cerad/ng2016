<?php

namespace AppBundle\Action\Schedule\Assignor;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\Schedule\ScheduleTemplate;

use Symfony\Component\HttpFoundation\Request;

class ScheduleAssignorView extends AbstractView2
{
    private $games;
    private $certifications;

    /** @var  ScheduleAssignorSearchForm */
    private $searchForm;

    /** @var  ScheduleTemplate */
    private $scheduleTemplate;

    public function __construct(
        ScheduleAssignorSearchForm $searchForm,
        ScheduleTemplate   $scheduleTemplate
    )
    {
        $this->searchForm = $searchForm;
        $this->scheduleTemplate = $scheduleTemplate;

    }
    public function __invoke(Request $request)
    {
        $this->games  = $request->attributes->get('games');
        $this->certifications  = $request->attributes->get('certifications');
        return $this->newResponse($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
{$this->searchForm->render()}
<hr>
{$this->scheduleTemplate->render($this->games, $this->certifications)}
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
}
