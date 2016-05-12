<?php
namespace AppBundle\Action\Project\Person\Admin\Update;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\User\ProjectUserRepository;

use Symfony\Component\HttpFoundation\Request;

class AdminUpdateForm extends AbstractForm
{
    /** @var  $projectControls */
    private $projectControls;
    
    /** @var  $projectControls */
    public $formControls;
    
    /** @var  $projectPersonViewDecorator */    
    private $personView;
    
    /** @var  ProjectPersonRepositoryV2 */
    private $projectUserRepository;
    
    private $requestUrl;

    public function __construct(
        $projectControls,
        $formControls,
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        ProjectUserRepository $projectUserRepository
    )
    {
        $this->projectControls = $projectControls;
        
        $formControls['YesNo'] = array(
            'type'      => 'select',
            'label'     => 'Yes / No',
            'default'   =>  'nr',
            'choices'   => ['no'=>'No','yes'=>'Yes','maybe'=>'Maybe','nr'=>'Not Required'],
        );
        
        $formControls['regYear'] = array(
            'type'      => 'select',
            'label'     => 'MemYear',
            'default'   =>  'my2016',
            'choices'   => ['my2016'=>'MY2016','my2015'=>'MY2015'],
        );

        foreach($formControls as $key => $meta)
        {
            if (!isset($meta['type'])) {
                $meta = array_merge($meta,$projectControls[$key]);
            }
            $this->formControls[$key] = $meta;
        }

        $this->personView = $projectPersonViewDecorator;

        $this->projectUserRepository = $projectUserRepository;

    }
    public function handleRequest(Request $request)
    {
        //stash the request url
        $projectPersonKey = $request->attributes->get('projectPersonKey');
        $this->requestUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['projectPersonKey'=>$projectPersonKey]
        );

        //check for post action
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;
    
        $errors = [];

        //get the form data
        $data = $request->request->all();

        //start with the original data as array
        $projectPerson = $this->formData;
        $orgKey = explode(':',$projectPerson['orgKey'])[0];
        $fedKey = explode(':',$projectPerson['fedKey'])[0];
        
        //update person array with form data
        $personData = $data;

        $projectPerson['name']      = $personData['name'];
        $projectPerson['email']     = $personData['email'];
        $projectPerson['age']       = $personData['age'];
        $projectPerson['phone']     = $personData['phone'];
        $projectPerson['shirtSize'] = $personData['shirtSize'];
        $projectPerson['fedKeyId']  = $fedKey . $personData['fedKeyId'];
        $projectPerson['orgkey']    = $orgKey . $personData['orgKeyRegion']; //todo: transform to sar/st
        $projectPerson['regYear']   = $personData['regYear'];
        
        //update plans
        $projectPersonPlans = $projectPerson['plans'];
        $personPlansData = $data['plans'];

        $projectPersonPlans['willReferee']      = $personPlansData['willReferee'];
        $projectPersonPlans['willVolunteer']    = $personPlansData['willVolunteer'];
        $projectPersonPlans['willCoach']        = $personPlansData['willCoach'];

        //update avail
        $projectPersonAvail = $projectPerson['avail'];

        //clear avail, $data only include set items
        foreach($projectPersonAvail as &$avail) {
            $avail = 'no';
        }
        if (isset($data['avail'])) {
            foreach ($data['avail'] as $avail) {
                $projectPersonAvail[$avail] = 'yes';
            }
        }   

        //update roles
        if (isset($projectPerson['roles']['CERT_REFEREE']) and
            isset($projectPerson['roles']['ROLE_REFEREE'])
            ) {
            $projectPersonCertReferee  = $projectPerson['roles']['CERT_REFEREE'];
            $projectPersonRoleReferee   = $projectPerson['roles']['ROLE_REFEREE'];
    
            $projectPersonCertReferee['badge']  = $personData['badge'];
            $projectPersonRoleReferee['approved']   = isset($personData['approved']) ? '1' : '0';
        }
var_dump($this->formData);
var_dump($projectPerson);
die();

        //update form data
        $this->setData($projectPerson);
        
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $person = new ProjectPerson;
        $person->fromArray($this->formData);
        $this->personView->setProjectPerson($person);

        $shirtChoices = [];
        $shirtKey = $this->formData['shirtSize'];
        foreach ($this->projectControls['shirtSize']['choices'] as $key => $choice) {
            $shirtChoices[$key] = $choice;
        }

        $html = <<<EOD
{$this->renderFormErrors()}

{$this->renderForm($this->personView)}

EOD;
        return $html;
    }
    public function renderForm($personView)
    {
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form method="post" action="{$this->requestUrl}" class="form-horizontal">
    <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
    {$this->renderProjectPerson($personView)}
</form>
EOD;
        return $html;
    }
    public function renderPanelFooter()
    {
        $html = <<<EOD
    <div class="panel-footer clearfix">
        <div class="pull-right">
            <a href="{$this->generateUrl('project_person_admin_listing')}" name="cancel" class="btn btn-sm btn-default ">Cancel</a>
            <button type="submit" name="save" class="btn btn-sm btn-default submit">
              <span class="glyphicon glyphicon-ok"></span> Save & Continue
            </button>
            <button type="submit"name="saveAndReturn" class="btn btn-sm btn-primary submit">
              <span class="glyphicon glyphicon-ok"></span> Save & Return to Listing
            </button>
        </div>
    </div>        
EOD;

        return $html;      
    }
    
    private function renderProjectPerson(ProjectPersonViewDecorator $personView)
    {
        $html = <<<EOD
{$this->renderRegistrationInfo($personView)}
{$this->renderAYSOInfo($personView)}
{$this->renderPlansInfo($personView)}
{$this->renderAvailInfo($personView)}
{$this->renderUserInfo($personView->person)}

EOD;
        return $html;
    }
    private function renderRegistrationInfo(ProjectPersonViewDecorator $personView)
    {
        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Registration Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regName">Name:</label>
      <input name="name" type="text" class="col-xs-4 form-control" id="regName" value="{$this->escape($personView->name)}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regEmail">Email:</label>
      <input name="email" type="text" class="col-xs-4 form-control" id="regEmail" value="{$this->escape($personView->email)}">
      <label class="col-xs-2 control-label" for="regAge">Age:</label>
      <input name="age" type="text" class="col-xs-4 form-control" id="regAge" value="{$this->escape($personView->age)}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regPhone">Phone:</label>
      <input name="phone" type="text" class="col-xs-4 form-control" id="regPhone" value="{$this->escape($personView->phone)}">
      <label class="col-xs-2 control-label" for="shirtSize">Shirt:</label>
      {$this->renderFormControl('shirtSize')}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;
    }
    private function renderAysoInfo(ProjectPersonViewDecorator $personView)
    {
        $certSH         = $personView->getCertClass('CERT_SAFE_HAVEN');
        $classSH        = ' '. (is_null($certSH) ? $personView->successClass : $certSH);

        $certConc       = $personView->getCertClass('CERT_CONCUSSION');
        $classConc      = ' '. (is_null($certConc) ? $personView->successClass : $certConc);

        $certBgCk       = $personView->getCertClass('CERT_BACKGROUND_CHECK');
        $classBgCk      = ' '. (is_null($certBgCk) ? $personView->successClass : $certBgCk);

        $certRef        = $personView->getCertClass('CERT_REFEREE');
        $classCertRef   = ' '. (is_null($certRef) ? $personView->successClass : $certRef);

        $roleRef        = $personView->getRoles();
        $roleRef        = $roleRef['ROLE_REFEREE'];
        $approvedRef    = isset($roleRef['approved']) ? $roleRef['approved'] : null; 
        $roleRef        = $personView->getRoleClass($roleRef);
        $classRoleRef   = ' '. (is_null($roleRef) ? $personView->successClass : $roleRef);
        
        $sar = explode('/', $personView->orgKey);
        $region = ltrim($sar[2], '0');
        $state = ltrim($sar[3], ' ');

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update AYSO Information</h1>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userAYSOId">AYSO ID:</label>
      <input name="fedKeyId" type="text" class="col-xs-2 form-control" id="userAYSOId" value="{$this->escape($personView->fedKey)}">
      <label class="col-xs-2 control-label" for="regYear">Mem Year:</label>
      {$this->renderFormControlInput($this->formControls['regYear'],$this->escape($personView->regYear),'regYear','regYear','col-xs-2 form-control')}
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userRegion">AYSO Region</label>
      <input name="orgKeyRegion" type="text" class="col-xs-4 form-control" id="userRegion" value="{$region}">
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="badge">Referee:</label>
      {$this->renderFormControlInput($this->formControls['refereeBadge'],$this->escape($personView->refereeBadge),'badge','badge','col-xs-4 form-control'.$classCertRef)}
      <label class="col-xs-2 control-label approved"><input name="approved" value="approved" type="checkbox" {$this->isChecked($approvedRef)}> Approved to Referee</label>
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userSH">Safe Haven:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->safeHavenCertified)),'userSH','userSH','col-xs-4 form-control'.$classSH)}      
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userConc">Concussion:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->concussionTrained)),'userConc','userConc','col-xs-4 form-control'.$classConc)}
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userBackground">FL Background Check:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->backgroundChecked)),'userBackground','userBackground','col-xs-4 form-control'.$classBgCk)}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;
    }
    private function renderPlansInfo(ProjectPersonViewDecorator $personView)
    {
        $notesUser = $personView->notesUser;
        if (strlen($notesUser) > 75) {
            $notesUser = substr($notesUser, 0, 75) . '...';
        }
        $notesUser = $this->escape($notesUser);

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Plans Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willReferee">Will Referee:</label>
      {$this->renderFormControl('willReferee')}
    </div>    
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willVolunteer">Will Volunteer:</label>
      {$this->renderFormControl('willVolunteer')}
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willCoach">Will Coach:</label>
      {$this->renderFormControl('willCoach')}
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="notesUser">User Notes:</label>
      {$this->renderFormControl('notesUser')}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;
        return $html;
    }
    private function renderAvailInfo(ProjectPersonViewDecorator $personView)
    {
        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Availabilty Information</h1>
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availWed" type="checkbox" {$this->isChecked($personView->availWed)}>Available Wed (Soccerfest)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availThu" type="checkbox" {$this->isChecked($personView->availThu)}>Available Thu (Pool Play)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availFri" type="checkbox" {$this->isChecked($personView->availFri)}>Available Fri (Pool Play)</label>
    </div>    
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSatMorn" type="checkbox" {$this->isChecked($personView->availSatMorn)}>Available Sat Morning (PP)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSatAfter" type="checkbox" {$this->isChecked($personView->availSatAfter)}>Available Sat Afternoon(QF)</label>
    </div>    
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunMorn" type="checkbox" {$this->isChecked($personView->availSunMorn)}>Available Sun Morning (SF)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunMorn" type="checkbox" {$this->isChecked($personView->availSunAfter)}>Available Sun Afternoon (FM)</label>
    </div>    
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;
    }
    private function renderUserInfo(ProjectPerson $person)
    {
        $user = $this->projectUserRepository->find($person->personKey);
        $enabled = $user['enabled'] ? 'Yes' : 'NO';
        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Plans Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="userName">Name:</label>
      <input name="userinfoName" type="text" class="col-xs-4 form-control" id="userName" value="{$this->escape($user['name'])}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="userEmail">Email:</label>
      <input name="userinfoEmail" type="text" class="col-xs-4 form-control" id="userEmail" value="{$this->escape($user['email'])}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="userUname">User:</label>
      <input name="userinfoUsername" type="text" class="col-xs-4 form-control" id="userUname" value="{$this->escape($user['username'])}">
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;

    }
    private function isChecked($value)
    {
        if($value == 'Yes') {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }

        return $checked;
    }
    public function renderFormControl($key,$class="form-control",$meta = null)
    {
        if (is_null($meta)) {
            $meta = $this->formControls[$key];
        }
        
        $group = isset($meta['group']) ? $meta['group'] : null;

        $id   = $group ? sprintf('%s_%s', $group,$key) : $key;
        $name = $group ? sprintf('%s[%s]',$group,$key) : $key;

        $default = isset($meta['default']) ? $meta['default'] : null;

        if ($group) {
            $value = isset($this->formData[$group][$key]) ? $this->formData[$group][$key] : $default;
        }
        else {

            $value = isset($this->formData[$key]) ? $this->formData[$key] : $default;
        }

        if (isset($meta['transformer'])) {
            $transformer = $this->getTransformer($meta['transformer']);
            $value = $transformer->transform($value);
        }
        $label = isset($meta['label']) ? $this->escape($meta['label']) : null;
        return <<<EOD
      {$this->renderFormControlInput($meta,$value,$id,$name,$class)}
EOD;
    }
    public function renderFormControlInput($meta,$value,$id,$name,$class="form-control")
    {
        $type = $meta['type'];

        switch($type) {

            case 'select':
                return $this->renderFormControlInputSelect($meta['choices'],$value,$id,$name,$class);

            case 'textarea':
                return $this->renderFormControlInputTextArea($meta,$value,$id,$name,$class);

        }
        return $this->renderFormControlInputText($meta,$value,$id,$name,$class);
    }
    private function renderFormControlInputText($meta,$value,$id,$name,$class="form-control")
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $value = $this->escape($value);

        return  <<<EOD
<input 
  type="{$meta['type']} id="{$id}" class="{$class}" {$required}
  name="{$name}" value="{$value}" placeHolder="{$placeHolder}"} />
EOD;
    }
    private function renderFormControlInputTextArea($meta,$value,$id,$name,$class)
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $rows = isset($meta['rows']) ? $meta['rows'] : 5;

        $value = $this->escape($value);

        return  <<<EOD
<textarea 
  id="{$id}" class="{$class}" rows="{$rows}" {$required}
  name="{$name}" placeHolder="{$placeHolder}"} >{$value}</textarea>
EOD;
    }
    protected function renderFormControlInputSelect($choices,$value,$id,$name,$class="form-control")
    {
        $html = <<<EOD
<select id="{$id}" name="{$name}" class="{$class}">
EOD;

        foreach($choices as $choiceValue => $choiceContent)
        {
            $selected = ($value === $choiceValue) ? ' selected' : null;
            $html .= <<<EOD
  <option value="{$choiceValue}"{$selected}>{$this->escape($choiceContent)}</option>
  
EOD;
        }
        $html .= <<<EOD
</select>
EOD;
        return $html;
    }

}