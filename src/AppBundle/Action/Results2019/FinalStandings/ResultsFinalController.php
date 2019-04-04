<?php

namespace AppBundle\Action\Results2019\FinalStandings;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Results2019\ResultsFinder;
use Symfony\Component\HttpFoundation\Request;

class ResultsFinalController extends AbstractController2
{
    private $searchForm;
    private $resultsFinder;

    private $projects;
    private $projectChoices;

    public function __construct(
        ResultsFinalSearchForm  $searchForm,
        ResultsFinder                $resultsFinder,
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
        // Support multiple projects
        $projectId = $this->getDefaultProjectId();
        $searchData = [
            'projectId' => $projectId,
            'program'   => $this->getDefaultProgramForProject($projectId),
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
            // Need a better way for this nonsense
            $searchDataNew = $this->searchForm->getData();
            if ($searchDataNew['projectId'] !== $searchData['projectId']) {
                $projectId = $searchDataNew['projectId'];
                $searchDataNew['program'] = $this->getDefaultProgramForProject($projectId);
            }
            $session->set('results_search_data_2016',$searchDataNew);
            return $this->redirectToRoute($this->getCurrentRouteName());
        }
        // Deal with query parameters
        if ($request->query->has('division')) {
            $searchData['division'] = $request->query->get('division');
            $searchData['poolKey']  = null;
            $session->set('results_search_data_2016', $searchData);
            //return $this->redirectToRoute($this->getCurrentRouteName());
        }
        $pools = [];
        $criteria = [
            'poolTypeKeys' => ['TF'], // TODO: Pull from project
            'projectIds'   => [$searchData['projectId']],
            'programs'     => [$searchData['program']],
        ];
        $results = $this->resultsFinder->findPools($criteria);
        $request->attributes->set('results',$results);

        return null;
    }
    private function getDefaultProjectId()
    {
        return array_keys($this->projectChoices)[0];
    }
    private function getDefaultProgramForProject($projectId)
    {
        return  array_keys($this->projects[$projectId]['programs'])[0];
    }
}