<?php
namespace AppBundle\Action\RegPerson\Teams\Update;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameOfficial\AssignWorkflow;

use AppBundle\Action\RegPerson\RegPersonFinder;
use AppBundle\Action\RegPerson\RegPersonPerson;
use Symfony\Component\HttpFoundation\Request;

class TeamsUpdateForm extends AbstractForm
{
    /** @var  RegPersonFinder */
    private $regPersonFinder;

    /** @var  RegPersonPerson[] */
    private $regPersonPersons = [];

    private $regPersonPersonAdd;
    private $regPersonPersonsRemove = [];
    
    public function __construct(
        RegPersonFinder $regPersonFinder
    ) {
        $this->regPersonFinder = $regPersonFinder;
    }
    public function setRegPersonPersons(array $regPersonPersons)
    {
        $this->regPersonPersons = $regPersonPersons;
    }
    public function getRegPersonPersonAdd() {
        return $this->regPersonPersonAdd;
    }
    public function getRegPersonPersonsRemove() {
        return $this->regPersonPersonsRemove;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();
        
        if ($data['submit'] === 'add') {
            $role     = $this->filterScalarString($data, 'role');
            $memberId = $this->filterScalarString($data, 'memberId');
            if ($role && $memberId) {
                $this->regPersonPersonAdd = [
                    'role'     => $role,
                    'memberId' => $memberId
                ];
            }
        }
        if ($data['submit'] === 'remove') {
            foreach($data['removeIds'] as $removeId) {
                if ($removeId) {
                    $this->regPersonPersonsRemove[] = $removeId;
                }
            }
        }
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $roleChoices = [
            null     => 'Select Relationship',
            'Family' => 'Family',
            'Peer'   => 'Peer',
        ];
        $memberChoices = array_merge(
            [null => 'Select Crew Member'],
            $this->regPersonFinder->findRegPersonChoices($this->getUser()->getProjectId())
        );

        $html = <<<EOD
<table style="min-width: 500px;">
  <tr><th colspan="3" style="text-align: center;">My Crew</th></tr>
  <tr><th>Relation</th><th>Name</th><th>Remove</th></tr>
<form method="post" action="{$this->generateUrl('reg_person_persons_update')}" class="form-inline role="form"">
EOD;
        foreach($this->regPersonPersons as $regPersonPerson) {
            $html .= $this->renderRegPersonPerson($regPersonPerson);
        }
        $html .= <<<EOD
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><button type="submit" name="submit" value="remove" class="btn btn-default">Remove Selected Person(s)</button></td>
  </tr>
</form>
</table>
<br/>
EOD;
        $html .= <<<EOD
<table style="min-width: 500px;">
  <tr><th colspan="3" style="text-align: center;">Add Person to Crew</th></tr>
</table>
<form method="post" action="{$this->generateUrl('reg_person_persons_update')}" class="form-inline role="form"">
  <div class="form-group">
      {$this->renderInputSelect($roleChoices,'Family','role','role')}
  </div>
  <div class="form-group">
      {$this->renderInputSelect($memberChoices,null,'memberId','memberId')}
  </div>
  <button type="submit" name="submit" value="add" class="btn btn-default">Add Person</button>
  <a href="{$this->generateUrl('app_home')}">Back To Home</a>
</form>
EOD;

        return $html;
    }
    private function renderRegPersonPerson(RegPersonPerson $regPersonPerson)
    {
        $role = $regPersonPerson->role;
        $name = $this->escape(($regPersonPerson->memberName));
        $remove = '&nbsp;';
        if ($role !== 'Primary') {
            $memberId = $regPersonPerson->memberId;
            $removeId = $role . ' ' . $memberId;
            $removeChoices = [
                 null     => 'Keep',
                $removeId => 'Remove',
            ];
            $remove = $this->renderInputSelect($removeChoices,null,'removeIds[]','removeIds');
        }
        return <<<EOD
  <tr><td>{$role}</td><td>{$name}</td><td>{$remove}</td></tr>
EOD;

    }
}
