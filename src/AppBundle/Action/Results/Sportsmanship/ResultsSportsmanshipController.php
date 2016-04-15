<?php

namespace AppBundle\Action\Results\Sportsmanship;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsSportsmanshipController extends AbstractController
{
    public function __construct()
    {
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
        $criteria['group_types'] = ['PP'];

        // Put criteria in session
        $request->attributes->set('project',$project);
        $request->attributes->set('criteria',$criteria);

        return null;

    }
}
