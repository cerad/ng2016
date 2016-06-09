<?php

namespace AppBundle\Action\Schedule2016\MedalRound;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Results2016\ResultsFinder;

use Symfony\Component\HttpFoundation\Request;

class ScheduleMedalRoundController extends AbstractController
{
    private $resultsFinder;
    private $projectChoices;
    private $projectId;
    
    public function __construct(
        ResultsFinder    $resultsFinder,
        array $projectChoices
    )
    {
        $this->resultsFinder = $resultsFinder;
        
        $this->projectChoices = $projectChoices;

        $this->projectId = $this->getDefaultProjectId();
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
            $criteria['poolSlotView'] = explode(',',$params['pools']);
        }

        $criteria['projectIds'] = [$this->projectId];
        $criteria['programs'] = ['Core'];
        $criteria['ages'] = ['U10','U12','U14','U16','U19'];
        $criteria['poolTypeKeys'] = ['PP'];

        $pools = $this->resultsFinder->findPools($criteria);

        // Put data in session
        $request->attributes->set('project',$project);
        $request->attributes->set('criteria',$criteria);
        $request->attributes->set('pools',$pools);
                
        return null;

    }
    private function getDefaultProjectId()
    {
        return array_keys($this->projectChoices)[1]; //NG2014
    }    
}
