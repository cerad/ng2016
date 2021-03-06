<?php
namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule\ScheduleTemplate;
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
{$this->searchForm->render()}
<hr>
{$this->scheduleTemplate->setTitle('Team Game Schedule')}
{$this->scheduleTemplate->render($this->games)}
EOD;
        return $this->renderBaseTemplate($content);
    }
 }
