<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Listing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\CurrentProject;
use Zayso\Reg\Person\Admin\AdminViewFilters;
use Zayso\Reg\Person\RegPersonFinder;

class AdminListingAction implements ActionInterface
{
    use RouterTrait;

    const SESSION_SEARCH_KEY = 'reg_person_admin_listing_search_data';

    private $project;
    private $template;
    private $searchForm;
    private $viewFilters;
    private $regPersonFinder;

    public function __construct(
        CurrentProject  $project,
        RegPersonFinder $regPersonFinder,
        AdminListingTemplate    $template,
        AdminListingSearchForm  $searchForm,
        AdminViewFilters        $viewFilters
    ) {
        $this->project         = $project;
        $this->template        = $template;
        $this->searchForm      = $searchForm;
        $this->viewFilters     = $viewFilters;
        $this->regPersonFinder = $regPersonFinder;
    }
    public function __invoke(Request $request) : Response
    {
        $searchData = [
            'projectKey'    => $this->project->projectId,
            'displayKey'    => 'Plans',
            'reportKey'     =>  null,
            'name'          =>  null,
        ];
        $session = $request->getSession();
        if ($session->has(self::SESSION_SEARCH_KEY)) {
            $searchData = array_merge($searchData,$session->get(self::SESSION_SEARCH_KEY));
        };

        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        
        $searchForm->handleRequest($request, $this->project, self::SESSION_SEARCH_KEY);

        if ($searchForm->isValid()) {

            $searchData = $searchForm->getData();

            $session->set(self::SESSION_SEARCH_KEY,$searchData);

            return $this->redirectToRoute('reg_person_admin_listing');
        }

        $displayKey = $searchData['displayKey'];
        $reportKey  = $searchData['reportKey'];

        $registered = null;
        $verified   = null;

        switch ($searchData['reportKey']) {
            case 'Unverified':
                $verified = false;
                break;
            default:
                $verified = null;
                break;
        }   
        
        $regPersons = $this->regPersonFinder->findByVarious($searchData['projectKey'],$searchData['name'],$registered,$verified);

        $regPersons = $this->viewFilters->filterByReport($regPersons,$reportKey);

        $content = $this->template->render($this->project, $this->searchForm, $regPersons, $displayKey);

        return new Response($this->project->pageTemplate->render($content));
    }
}
