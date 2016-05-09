<?php

namespace AppBundle\Action\Results2016\PoolPlay;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Results2016\ResultsFinder;
use Symfony\Component\HttpFoundation\Request;

class ResultsPoolPlayController extends AbstractController2
{
    private $searchForm;
    private $resultsFinder;

    private $projects;
    private $projectChoices;

    public function __construct(
        ResultsPoolPlaySearchForm  $searchForm,
        ResultsFinder              $resultsFinder,
        array $projectChoices,
        array $projects
    )
    {
        $this->searchForm    = $searchForm;
        $this->resultsFinder = $resultsFinder;

        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
    }
    public function __invoke(Request $request)
    {
        // Search data is just for switching between projects and programs
        $projectId = array_keys($this->projectChoices)[0];
        $program   = array_keys($this->projects[$projectId]['programs'])[0];
        $searchData = [
            'projectId' => $projectId,
            'program'   => $program,
        ];
        // Override from session
        $session = $request->getSession();
        $sessionKey = 'results_search_data_2016';
        if ($session->has($sessionKey)) {
            $searchData = array_replace($searchData,$session->get($sessionKey));
        }
        
        // The form
        $this->searchForm->setData($searchData);
        $this->searchForm->handleRequest($request);
        if ($this->searchForm->isValid()) {
            $searchData = $this->searchForm->getData();
            $session->set('results_search_data_2016',$searchData);
            return $this->redirectToRoute($this->getCurrentRouteName(),[
                'projectId'   => $projectId,
                'poolKey'     => $poolKey,
                'poolTeamKey' => $poolTeamKey,
            ]);
        }
        $request->attributes->set('pools',[]);
    }
}
