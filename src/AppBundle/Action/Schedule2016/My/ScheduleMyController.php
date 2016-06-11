<?php

namespace AppBundle\Action\Schedule2016\My;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule2016\ScheduleFinder;

use AppBundle\Action\Schedule2016\ScheduleSearchForm;

use Symfony\Component\HttpFoundation\Request;

class ScheduleMyController extends AbstractController2
{
    private $searchForm;    // Not bein used but keep for pattern
    private $scheduleFinder;

    public function __construct(
        ScheduleMySearchForm $searchForm,
        ScheduleFinder       $scheduleFinder
    ) {
        $this->searchForm     = $searchForm;
        $this->scheduleFinder = $scheduleFinder;
    }
    public function __invoke(Request $request)
    {
        $criteria = [
            'projectIds'    => [$this->getCurrentProjectKey()],
            'regPersonId'   => $this->getUserRegPersonId(),
            'doGeneral'     => false,
            'wantOfficials' => true,
        ];
        $games = $this->scheduleFinder->findGames($criteria);

        $request->attributes->set('games', $games);

        return null;
    }
}
