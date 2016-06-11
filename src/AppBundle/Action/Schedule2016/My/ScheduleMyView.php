<?php

namespace AppBundle\Action\Schedule2016\My;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleTemplate;
use Symfony\Component\HttpFoundation\Request;

class ScheduleMyView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;

    private $searchForm; // Keep for now, not being used
    private $scheduleTemplate;

    public function __construct(
        ScheduleMySearchForm $searchForm,
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
{$this->scheduleTemplate->render($this->games)}
EOD;
        return $this->renderBaseTemplate($content);
    }
}
