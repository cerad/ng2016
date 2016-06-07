<?php
namespace AppBundle\Action\RegPerson\Persons\Update;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameOfficial\AssignWorkflow;

use AppBundle\Action\RegPerson\RegPersonFinder;
use AppBundle\Action\RegPerson\RegPersonPerson;
use Symfony\Component\HttpFoundation\Request;

class PersonsUpdateForm extends AbstractForm
{
    /** @var  RegPersonFinder */
    private $regPersonFinder;

    /** @var  RegPersonPerson[] */
    private $regPersonPersons = [];

    private $addPerson;
    private $removePersonIds = [];
    
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
        return $this->addPerson;
    }
    public function getRegPersonPersonsRemove() {
        return $this->removePersonIds;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();
        
        // Adding
        $addRole     =  $this->filterScalarString($data, 'addRole');
        $addMemberId =  $this->filterScalarString($data, 'addMemberId');
        if ($addRole && $addMemberId) {
            $this->addPerson = [
                'role'     => $addRole,
                'memberId' => $addMemberId
            ];
        }
        // Removing
        $removeIds = $this->filterArray($data,'removeIds');
        foreach($removeIds as $removeId) {
            if ($removeId) {
                $this->removePersonIds[] = $removeId;
            }
        }
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $addChoices = [
            'add' => 'Add',
        ];

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
  <tr><th>Relation</th><th>Name</th><th>Add/Remove</th></tr>
<form method="post" action="{$this->generateUrl('reg_person_persons_update')}" class="form-inline role="form"">
EOD;
        foreach($this->regPersonPersons as $regPersonPerson) {
            $html .= $this->renderRegPersonPerson($regPersonPerson);
        }
        $html .= <<<EOD
  <tr>
    <td>{$this->renderInputSelect($roleChoices,'Family','addRole',    'addRole')}</td>
    <td>{$this->renderInputSelect($memberChoices,null,  'addMemberId','addMemberId')}</td>
    <td>{$this->renderInputSelect($addChoices,'add',    'addChoice',  'addChoice')}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="{$this->generateUrl('app_home')}">Back To Home</a></td>
    <td><button type="submit" name="submit" value="submit" class="btn btn-default">Add/Remove Selected Person(s)</button></td>
  </tr>
</form>
</table>
<br/>
EOD;
        return $html;
    }
    private function renderRegPersonPerson(RegPersonPerson $regPersonPerson)
    {
        $role = $regPersonPerson->role;
        $name = $this->escape(($regPersonPerson->memberName));
        $removeChoices = [null => 'Keep'];
        if ($role !== 'Primary') {
            $memberId = $regPersonPerson->memberId;
            $removeId = $role . ' ' . $memberId;
            $removeChoices = [
                 null     => 'Keep',
                $removeId => 'Remove',
            ];
        }
        $remove = $this->renderInputSelect($removeChoices,null,'removeIds[]','removeIds');

        return <<<EOD
  <tr><td>{$role}</td><td>{$name}</td><td>{$remove}</td></tr>
EOD;

    }
}
