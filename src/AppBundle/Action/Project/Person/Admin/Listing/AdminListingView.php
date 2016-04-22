<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\User\ProjectUserRepository;
use Symfony\Component\HttpFoundation\Request;

class AdminListingView extends AbstractView2
{
    private $searchForm;

    /** @var  ProjectPerson[] */
    private $projectPersons;

    private $projectUserRepository;

    private $projectPersonRepository;

    private $projectPersonViewDecorator;

    public function __construct(
        ProjectPersonRepositoryV2  $projectPersonRepository,
        ProjectUserRepository      $projectUserRepository,
        AdminListingSearchForm     $searchForm,
        ProjectPersonViewDecorator $projectPersonViewDecorator
    )
    {
        $this->searchForm = $searchForm;
        $this->projectUserRepository      = $projectUserRepository;
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
  <th>User Information</th>
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles</th>
  <th>Plans</th>
  <th>Availability</th>
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
  <td>{$this->renderUserInfo        ($person,$personView)}</td>
  <td>{$this->renderRegistrationInfo($person,$personView)}</td>
  <td>{$this->renderAysoInfo        ($person,$personView)}</td>
  <td>{$this->renderRoles           ($person,$personView)}</td>
  <td>{$this->renderPlansInfo       ($person,$personView)}</td>
  <td>{$this->renderAvailInfo       ($person,$personView)}</td>
</tr>
EOD;
    }
    private function renderRegistrationInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        $href = $this->generateUrl('project_person_admin_update',['projectPersonKey' => $person->getKey()]);

        $gage = $personView->gender . $personView->age;
        return <<<EOD
<table>
  <tr><td>Name  </td><td><a href="{$href}">{$this->escape($personView->name)}</a></td></tr>
  <tr><td>Email </td><td>{$this->escape($personView->email)} </td></tr>
  <tr><td>Phone </td><td>{$this->escape($personView->phone)} </td></tr>
  <tr><td>G Age</td><td> {$this->escape($gage)}</td></tr>
  <tr><td>Shirt </td><td>{$this->escape($personView->shirtSize)}</td></tr>
</table>
EOD;

    }
    private function renderAysoInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        return <<<EOD
<table>
  <tr><td>AYSO ID   </td><td>{$this->escape($personView->fedKey)} </td></tr>
  <tr><td>Mem Year  </td><td>{$this->escape($personView->regYear)}</td></tr>
  <tr><td>SAR       </td><td>{$this->escape($personView->orgKey)} </td></tr>
  <tr><td>Referee   </td><td>{$this->escape($personView->refereeBadge)}</td></tr>
  <tr><td>Safe Haven</td><td>{$this->escape($personView->safeHavenCertified)}</td></tr>
  <tr><td>Concussion</td><td>{$this->escape($personView->concussionTrained)}</td></tr>
</table>
EOD;
    }
    private function renderPlansInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        $notesUser = $personView->notesUser;
        if (strlen($notesUser) > 75) {
            $notesUser = substr($notesUser, 0, 75) . '...';
        }
        $notesUser = $this->escape($notesUser);

        return <<<EOD
<table>
  <tr><td>Will  Referee  </td><td>{$personView->willReferee}  </td></tr>
  <tr><td>Will  Volunteer</td><td>{$personView->willVolunteer}</td></tr>
  <tr><td>Will  Coach    </td><td>{$personView->willCoach}    </td></tr>
  <tr><td colspan="2" style="max-width: 150px; ">{$notesUser}</td></tr>
</table>
EOD;

    }
    private function renderAvailInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {

        return <<<EOD
<table>
  <tr><td>Avail Wednesday</td><td>{$personView->availWed}     </td></tr>
  <tr><td>Avail Thursday </td><td>{$personView->availThu}     </td></tr>
  <tr><td>Avail Friday   </td><td>{$personView->availFri}     </td></tr>
  <tr><td>Avail Sat Morn </td><td>{$personView->availSatMorn} </td></tr>
  <tr><td>Avail Sat After</td><td>{$personView->availSatAfter}</td></tr>
  <tr><td>Avail Sun Morn </td><td>{$personView->availSunMorn} </td></tr>
  <tr><td>Avail Sun After</td><td>{$personView->availSunAfter}</td></tr>
</table>
EOD;

    }
    private function renderRoles(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        $html = <<<EOD
<table>
EOD;
        foreach($person['roles'] as $role) {

            $html .= <<<EOD
<tr><td>{$role->role}</td></tr>   
EOD;
        }
        $html .= <<<EOD
</table>
EOD;
        return $html;
    }
    private function renderUserInfo(ProjectPerson $person, ProjectPersonViewDecorator $personView)
    {
        $user = $this->projectUserRepository->find($person->personKey);
        $enabled = $user['enabled'] ? 'Yes' : 'NO';
        return <<<EOD
<table>
  <tr><td>Name   </td><td>{$this->escape($user['name'])}    </td></tr>
  <tr><td>Email  </td><td>{$this->escape($user['email'])}   </td></tr>
  <tr><td>User   </td><td>{$this->escape($user['username'])}</td></tr>
  <tr><td>Enabled</td><td>{$enabled}</td></tr>
</table>
EOD;

    }

}
