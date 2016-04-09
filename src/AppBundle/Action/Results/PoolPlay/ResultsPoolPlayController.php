<?php

namespace AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Schedule\ScheduleRepository;
use AppBundle\Action\Results\PoolPlay\Calculator\StandingsCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsPoolPlayController extends AbstractController
{
    public function __construct(
    //    ScheduleRepository  $scheduleRepository,
    //    StandingsCalculator $standingsCalculator
    )
    {
    //    $this->scheduleRepository  = $scheduleRepository;
    //    $this->standingsCalculator = $standingsCalculator;
    //    
    //    //session_abort();
    //    //session_start();
    //    //$_SESSION["RETURN_TO_URL"] = $_SERVER['REQUEST_URI'];
    //
    }
    public function __invoke(Request $request)
    {
        $project = [];

        $project = $this->getCurrentProject()['info'];

        $params = $request->query->all();

        $criteria = [];

        if (isset($params['project']) && $params['project']) {
            $criteria['projects'] = explode(',',$params['project']);
        }
        if (isset($params['programs']) && $params['programs']) {
            $criteria['programs'] = explode(',',$params['programs']);
        }
        if (isset($params['genders']) && $params['genders']) {
            $criteria['genders'] = explode(',',$params['genders']);
        }
        if (isset($params['ages']) && $params['ages']) {
            $criteria['ages'] = explode(',',$params['ages']);
        }
        if (isset($params['pools']) && $params['pools']) {
            $criteria['group_names'] = explode(',',$params['pools']);
        }
        $criteria['group_types'] = ['PP'];

        // Put criteria in session
        $request->attributes->set('project',$project);
        $request->attributes->set('criteria',$criteria);

        return null;

    }
}
