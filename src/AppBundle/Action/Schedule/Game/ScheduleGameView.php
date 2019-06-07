<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule\ScheduleGame;
use AppBundle\Action\Schedule\ScheduleTemplate;

use Symfony\Component\HttpFoundation\Request;

class ScheduleGameView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;
    
    private $searchForm;
    private $scheduleTemplate;

    public function __construct(
        ScheduleGameSearchForm $searchForm,
        ScheduleTemplate $scheduleTemplate
    )
    {
        $this->searchForm       = $searchForm;
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
<br />
{$this->scheduleTemplate->render($this->games)}
<br />
EOD;

        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
}
