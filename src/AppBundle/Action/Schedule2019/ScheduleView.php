<?php

namespace AppBundle\Action\Schedule2019;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class ScheduleView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;

    private $searchForm;
    private $scheduleTemplate;

    public function __construct(
        ScheduleSearchForm $searchForm,
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
