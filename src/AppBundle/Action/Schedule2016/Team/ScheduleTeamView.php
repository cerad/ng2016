<?php
namespace AppBundle\Action\Schedule2016\Team;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\User\ProjectUserRepository;
use Symfony\Component\HttpFoundation\Request;

class ScheduleTeamView extends AbstractView2
{
    private $searchForm;
    private $displayKey;

    /** @var  ProjectPerson[] */
    private $projectPersons;
    private $projectPersonsCount;

    private $projectUserRepository;

    private $projectPersonRepository;

    private $projectPersonViewDecorator;

    public function __construct(
        ScheduleTeamSearchForm $searchForm
    )
    {
        $this->searchForm = $searchForm;
    }
    public function __invoke(Request $request)
    {
        //$this->displayKey     = $request->attributes->get('displayKey');
        //$this->projectPersons = $request->attributes->get('projectPersons');
        //$this->projectPersonsCount = count($this->projectPersons);

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Team Schedules</legend>
{$this->searchForm->render()}
<br/>
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderProjectPersons()
    {
        $html = <<<EOD
<table class='table'>
<tr>
EOD;
        switch($this->displayKey) {

            case 'Plans':
                $html .= <<<EOD
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Plans</th>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
  <th>Registration Information</th>
  <th>User Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
</tr>
EOD;
                break;

            case 'Avail':
                $html .= <<<EOD
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Availability</th>
</tr>
EOD;
                break;
        }

        foreach($this->projectPersons as $person) {

            // Should this be a private variable to be consistent?
            $personView = $this->projectPersonViewDecorator;

            $personView->setProjectPerson($person);

            $html .= $this->renderProjectPerson($personView);
        }
        $html .= <<<EOD
</table>

EOD;

        return $html;
    }
    private function renderProjectPerson(ProjectPersonViewDecorator $personView)
    {
        $html = null;
        switch($this->displayKey) {

            case 'Plans':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderPlansInfo       ($personView)}</td>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderUserInfo        ($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
</tr>
EOD;
                break;
            
            case 'Avail':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderAvailInfo       ($personView)}</td>
</tr>
EOD;
                break;
        }
        return $html;
    }
    private function renderRegistrationInfo(ProjectPersonViewDecorator $personView)
    {
        $href = $this->generateUrl('project_person_admin_update',['projectPersonKey' => $personView->getKey()]);

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
    // TODO Pull ayso name,email,phone if available
    private function renderAysoInfo(ProjectPersonViewDecorator $personView)
    {
        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

        return <<<EOD
<table>
  <tr>
    <td>AYSO ID</td>
    <td>{$personView->fedId}</td>
  </tr><tr>
    <td>SAR</td>
    <td class="{$personView->getOrgKeyClass()}">{$personView->orgKey}</td>
  </tr><tr>
    <td>Mem Year</td>
    <td class="{$personView->getRegYearClass($regYearProject)}">{$personView->getRegYear($regYearProject)}</td>
  </tr>
</table>
EOD;
    }
    private function renderPlansInfo(ProjectPersonViewDecorator $personView)
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
    private function renderAvailInfo(ProjectPersonViewDecorator $personView)
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
    private function renderRoles(ProjectPersonViewDecorator $personView)
    {
        $html = <<<EOD
<table>
EOD;
        foreach($personView->getRoles() as $role) {

            $html .= <<<EOD
<tr><td class="{$personView->getRoleClass($role)}">{$role->role}</td></tr>   
EOD;
        }
        foreach($personView->getCerts() as $cert) {

            $certKey = $cert->role;

            $html .= <<<EOD
<tr><td class="{$personView->getCertClass($certKey)}">{$personView->getCertBadge($certKey)}</td></tr>   
EOD;
        }
        $html .= <<<EOD
</table>
EOD;
        return $html;
    }
    private function renderUserInfo(ProjectPersonViewDecorator $personView)
    {
        $user = $this->projectUserRepository->find($personView->personKey);
        $enabled = $user['enabled'] ? 'Yes' : 'NO';
        $roles = implode(',',$user['roles']);
        return <<<EOD
<table>
  <tr><td>Name   </td><td>{$this->escape($user['name'])}    </td></tr>
  <tr><td>Email  </td><td>{$this->escape($user['email'])}   </td></tr>
  <tr><td>User   </td><td>{$this->escape($user['username'])}</td></tr>
  <tr><td>Enabled</td><td>{$enabled}</td></tr>
  <tr><td>Roles  </td><td>{$roles}  </td></tr>
</table>
EOD;

    }

}
