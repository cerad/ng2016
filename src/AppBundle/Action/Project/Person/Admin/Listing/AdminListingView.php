<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminListingView extends AbstractView2
{
    private $searchForm;

    /** @var  ProjectPerson[] */
    private $projectPersons;

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
        $this->projectPersons = $request->attributes->get('projectPersons');

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Project Person Listing</legend>
{$this->searchForm->render()}
<br/>
{$this->renderProjectPersons()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderProjectPersons()
    {
        $html = <<<EOD
<table class='table'>
EOD;

        foreach($this->projectPersons as $projectPerson) {
            $html .= $this->renderProjectPerson($projectPerson);
        }
        $html .= <<<EOD
<table class='table'>
EOD;

        return $html;
    }
    private function renderProjectPerson($person)
    {
        return <<<EOD
<tr>
  <td>{$person['name']}</td>
</tr>
EOD;
    }
}
