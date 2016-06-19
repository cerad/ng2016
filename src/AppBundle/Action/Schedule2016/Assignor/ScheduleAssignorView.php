<?php

namespace AppBundle\Action\Schedule2016\Assignor;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\Schedule2016\ScheduleTemplate;

use Symfony\Component\HttpFoundation\Request;

class ScheduleAssignorView extends AbstractView2
{
    private $games;

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
{$this->scheduleTemplate->render($this->games)}
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
}
