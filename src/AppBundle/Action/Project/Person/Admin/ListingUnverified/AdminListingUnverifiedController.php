<?php
namespace AppBundle\Action\Project\Person\Admin\ListingUnverified;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminListingUnverifiedController extends AbstractController
{
    private $searchForm;
    private $projectPersonRepository;
    
    public function __construct(
        ProjectPersonRepositoryV2 $projectPersonRepository,
        AdminListingUnverifiedSearchForm    $searchForm
    )
    {
        $this->searchForm = $searchForm;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request)
    {

        $searchData = [
            'projectKey' => $this->getCurrentProjectKey(),
            'displayKey' => 'Plans',
            'name'       =>  null,
        ];
        $session = $request->getSession();
        if ($session->has('project_person_admin_listing_unverified_search_data')) {
            $searchData = array_merge($searchData,$session->get('project_person_admin_listing_unverified_search_data'));
        };
        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        
        $searchForm->handleRequest($request);
        if ($searchForm->isValid()) {

            $searchData = $searchForm->getData();

            $session->set('project_person_admin_listing_unverified_search_data',$searchData);

            return $this->redirectToRoute('project_person_admin_listing_unverified');
        }
        $projectPersons = $this->projectPersonRepository->findByProjectKey($searchData['projectKey'],$searchData['name'],true,false);
        
        $request->attributes->set('projectPersons',$projectPersons);
        
        $request->attributes->set('displayKey',$searchData['displayKey']);
        
        return null;
    }
}
