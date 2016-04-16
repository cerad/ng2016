<?php

namespace AppBundle\Action\App\Home;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Project\Person\ProjectPersonRepository;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class HomeView extends AbstractView
{
    private $user;
    private $projectPerson;
    private $projectPersonRepository;
    
    public function __construct(ProjectPersonRepository $projectPersonRepository)
    {
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request)
    {
        $this->user = $user = $this->getUser();
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        $this->projectPerson = $this->projectPersonRepository->find($projectKey,$personKey);

        if (!$this->projectPerson) {
            throw new InternalErrorException('No project person in the home view for ' . $user['name']);
        }
        return new Response($this->renderPage());
    }

    protected function renderPage()
    {
        $content = <<<EOD
<legend>Home Page</legend>
<div class="float-right" id="user">
{$this->renderUser()}
</div>
<div class="account-person-list">
{$this->renderAccountInformation()}
{$this->renderMoreInformation()}
</div>
EOD;

        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
    /* ====================================================
     * Just a hack for now, later move to header section
     *
     */
    protected function renderUser()
    {
        return <<<EOD
User: {$this->escape($this->user['name'])}
EOD;
    }
    /* ====================================================
     * Account Information
     *
     */
    protected function renderAccountInformation()
    {
        $user = $this->user;

        return <<<EOD
<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">Account Information</th></tr>
  <tr><td>Name:   </td><td>{$user['name']}</td></tr>
  <tr><td>Account:</td><td>{$user['username']}</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="/person-person/update/1">
        Update your account
    </a>
  </td></tr>
</table>
EOD;
    }
    /* ==========================================
     * Rest of the info, break out later
     *
     */
    protected function renderMoreInformation()
    {
        return <<<EOD
<table  class="account-person-list app_table" border="1" >
  <tr><th colspan="2">AYSO Information</th></tr>
  <tr><td>AYSO ID:</td>   <td>99782945</td></tr>
  <tr><td>Vol Year:</td>  <td>MY2013</td></tr>
  <tr><td>Safe Haven:</td><td>AYSO</td></tr>
  <tr><td>Ref Badge:</td> <td>Advanced</td></tr>
  <tr><td>Region:</td>    <td>5/C/894</td></tr>
</table>

<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">Tournament Plans</th></tr>
  <tr><td>Will Attend: </td><td>yes</td></tr>
  <tr><td>Will Referee:</td><td>yes</td></tr>
  <tr><td>Program:     </td><td>core</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('project_person_register')}">
      Update your plans
    </a>
  </td></tr>
</table>

<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Teams</th></tr>
  <tr><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/teams?_back=%2Fhome">Add/Remove Teams
    </a>
  </td></tr>
</table>

<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Crew</th></tr>
  <tr><td style="text-align: center;" colspan="2">
    Primary: Art Hundiak
  </td></tr>
  <tr><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/persons?_back=%2Fhome">Add/Remove People
    </a>
  </td></tr>
</table>
EOD;
    }
}
