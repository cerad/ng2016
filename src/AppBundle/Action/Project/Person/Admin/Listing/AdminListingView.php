<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use Symfony\Component\HttpFoundation\Request;

class AdminListingView extends AbstractView2
{
    private $searchForm;

    /** @var  ProjectPerson[] */
    private $projectPersons;

    private $projectPersonRepository;

    private $projectPersonViewDecorator;

    public function __construct(
        ProjectPersonRepositoryV2  $projectPersonRepository,
        AdminListingSearchForm     $searchForm,
        ProjectPersonViewDecorator $projectPersonViewDecorator
    )
    {
        $this->searchForm = $searchForm;
        $this->projectPersonRepository    = $projectPersonRepository;
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
    }
    public function __invoke(Request $request)
    {
        $this->projectPersons = $request->attributes->get('projectPersons');

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Registered Person Listing</legend>
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
<tr>
  <th>Registration Information</th>
  <th>AYSO Information</th>
</tr>
EOD;

        foreach($this->projectPersons as $projectPerson) {
            $html .= $this->renderProjectPerson($projectPerson);
        }
        $html .= <<<EOD
</table>

EOD;

        return $html;
    }
    private function renderProjectPerson(ProjectPerson $person)
    {
        $personView = $this->projectPersonViewDecorator;
        $personView->setProjectPerson($person);

        return <<<EOD
<tr id="project-person-{$person->getKey()}">
  <td>{$this->renderRegistrationInfo($person,$personView)}</td>
  <td>{$this->renderAysoInfo        ($person,$personView)}</td>
</tr>
EOD;
    }
    private function renderRegistrationInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        $href = $this->generateUrl('project_person_admin_update',['projectPersonKey' => $person->getKey()]);

        return <<<EOD
<table>
  <tr><td>Name  </td><td><a href="{$href}">{$this->escape($person->name)}</a></td></tr>
  <tr><td>Email </td><td>{$this->escape($person->email)} </td></tr>
  <tr><td>Phone </td><td>{$this->escape($personView->phone)} </td></tr>
  <tr><td>Gender</td><td>{$this->escape($person->gender)}</td></tr>
  <tr><td>Age   </td><td>{$this->escape($person->age)}   </td></tr>
</table>
EOD;

    }
    private function renderAysoInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        return <<<EOD
<table>
  <tr><td>AYSO ID   </td><td>{$this->escape($personView->fedKey)}</td></tr>
  <tr><td>Mem Year  </td><td>{$this->escape($personView->regYear)}</td></tr>
  <tr><td>SAR       </td><td>{$this->escape($personView->orgKey)}</td></tr>
  <tr><td>Referee   </td><td>{$this->escape($personView->refereeBadge)}</td></tr>
  <tr><td>Safe Haven</td><td>{$this->escape($personView->safeHavenCertified)}</td></tr>
  <tr><td>Concussion</td><td>{$this->escape($personView->concussionTrained)}</td></tr>
</table>
EOD;
    }
}
