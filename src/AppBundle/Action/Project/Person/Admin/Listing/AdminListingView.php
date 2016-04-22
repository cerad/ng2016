<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminListingView extends AbstractView2
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
        return $this->newResponse($this->render());
    }
    private function render()
    {
        //$project = $this->getCurrentProjectInfo();

        $content = <<<EOD
<legend>Project Person Listing</legend>
{$this->searchForm->render()}
EOD;
        return $this->renderBaseTemplate($content);
    }
}
