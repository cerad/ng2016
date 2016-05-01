<?php
namespace AppBundle\Action\Schedule2016\Team;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule2016\ScheduleTemplate;
use Symfony\Component\HttpFoundation\Request;

class ScheduleTeamView extends AbstractView2
{
    private $searchForm;
    private $scheduleTemplate;
    private $games;

    public function __construct(
        ScheduleTeamSearchForm $searchForm,
        ScheduleTemplate $scheduleTemplate
    )
    {
        $this->searchForm = $searchForm;
        $this->scheduleTemplate = $scheduleTemplate;
    }
    public function __invoke(Request $request)
    {
        $this->games = $request->attributes->get('games');

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Team Schedules</legend>
{$this->searchForm->render()}
<br/>
{$this->scheduleTemplate->render($this->games)}
EOD;
        return $this->renderBaseTemplate($content);
    }
 }
