<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminListingController extends AbstractController
{
    private $searchForm;
    private $projectPersonRepository;
    
    public function __construct(
        ProjectPersonRepositoryV2 $projectPersonRepository,
        AdminListingSearchForm    $searchForm
    )
    {
        $this->searchForm = $searchForm;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request)
    {

        $searchData = [
            'projectKey' => $this->getCurrentProjectKey(),
            'name'       =>  'Hun',//null,
        ];
        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        
        $searchForm->handleRequest($request);
        if ($searchForm->isValid()) {

            $searchData = $searchForm->getData();
            
            //return $this->redirectToRoute('project_person_admin_listing');
        }
        $projectPersons = $this->projectPersonRepository->findByProjectKey($searchData['projectKey'],$searchData['name']);
        
        $request->attributes->set('projectPersons',$projectPersons);
        
        return null;
    }
}
