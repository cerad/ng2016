<?php

namespace AppBundle\Action\Results\FinalStandings;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;

class ResultsFinalController extends AbstractController
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

        $criteria['programs'] = $project['programs'];
        $criteria['group_types'] = ['FM'];

        // Put criteria in session
        $request->attributes->set('project',$project);
        $request->attributes->set('criteria',$criteria);

        return null;

    }
}
