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
        
        foreach($projectControls as $key => $meta)
        {
            if (!isset($meta['type'])) {
                $meta = array_merge($meta,$projectControls[$key]);
            }
            $this->formControls[$key] = $meta;
        }

        $this->formControls['YesNo'] = array(
            'type'      => 'select',
            'label'     => 'Yes / No',
            'default'   =>  'no',
            'choices'   => ['yes'=>'Yes','no'=>'No','maybe'=>'Maybe','nr'=>'Not Required'],
        );
        
        $this->formControls['regYear'] = array(
            'type'      => 'select',
            'label'     => 'MemYear',
            'default'   =>  null,
            'choices'   => ['MY2019'=>'MY2019','MY2018'=>'MY2018',null=>'***'],
        );

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
        $projectPersonArray = $this->formData;
        $projectPerson = new ProjectPerson;
        $projectPerson->fromArray($projectPersonArray);

        $orgKey = explode(':',$projectPerson->orgKey)[0];
        $fedKey = explode(':',$projectPerson->fedKey)[0];

        $orgKey = empty($orgKey) ? 'AYSOR' : $orgKey;
        $fedKey = empty($fedKey) ? 'AYSOV' : $fedKey;
        
        //update person array with form data
        $personData = $data;

        $strRegion = str_pad($this->filterScalarString($data,'orgKeyRegion'),4,'0',STR_PAD_LEFT);
        
        $projectPerson->name      = $this->filterScalarString($data,'name');
        $projectPerson->email     = $this->filterScalarString($data,'email');
        $projectPerson->age       = $this->filterScalarString($data,'age');
        $projectPerson->phone     = $this->filterScalarString($data,'phone');
        $projectPerson->shirtSize = $personData['shirtSize'];
        $projectPerson->fedKey    = $fedKey . ":" . $this->filterScalarString($data,'fedKeyId');
        $projectPerson->orgKey    = $orgKey . ":" . $strRegion;
        $projectPerson->regYear   = strtoupper($personData['regYear']);

        //update plans
        $projectPersonPlans = &$projectPerson->plans;

        $projectPersonPlans['willReferee']      = $personData['willReferee'];
        $projectPersonPlans['willVolunteer']    = $personData['willVolunteer'];
        $projectPersonPlans['willCoach']        = $personData['willCoach'];

        $projectPerson['notesUser']             = $this->filterScalarString($data,'notesUser');

        //update avail
        $projectPersonAvail = &$projectPerson->avail;
        
        //reset like this, Blue Sombrero does not have this field
        $projectPerson->avail = [];
        $projectPerson->avail = [
            'availTue'      => 'no',
            'availWed'      => 'no',
            'availThu'      => 'no',
            'availFri'      => 'no',
            'availSatMorn'  => 'no',
            'availSatAfter' => 'no',
            'availSunMorn'  => 'no',
            'availSunAfter' => 'no',
        ];

        if (isset($data['avail'])) {
            foreach ($data['avail'] as $avail) {
                $projectPersonAvail[$avail] = 'yes';
            }
        };

        $projectPerson['notes']             = $this->filterScalarString($data,'notes');
        //update certs
        if (isset($projectPerson->roles['CERT_SAFE_HAVEN'])) {
            $projectPerson->roles['CERT_SAFE_HAVEN']['verified'] = $personData['userSH'] === 'yes' ? true : null;
        }
        if (isset($projectPerson->roles['CERT_CONCUSSION'])) {
            $projectPerson->roles['CERT_CONCUSSION']['verified'] = $personData['userConc'] === 'yes' ? true : null;            
        }

        //update roles
        if (isset($projectPerson->roles['CERT_REFEREE']) and
            isset($projectPerson->roles['ROLE_REFEREE'])
            ) {
            $projectPersonCertReferee   = &$projectPerson->roles['CERT_REFEREE'];
            $projectPersonRoleReferee   = &$projectPerson->roles['ROLE_REFEREE'];
    
            $projectPersonCertReferee['badge']  = $personData['badge'];
            $projectPersonRoleReferee['approved']   = isset($personData['approvedRef']) ? true : null;
            if ($projectPersonRoleReferee['approved']) {
                $projectPersonCertReferee['verified'] = true;
            }
        }

        if (isset($projectPerson->roles['ROLE_VOLUNTEER'])) {
            $projectPersonRoleVolunteer   = &$projectPerson->roles['ROLE_VOLUNTEER'];

            $projectPersonRoleVolunteer['approved']   = isset($personData['approvedVol']) ? true : null;
            if ($projectPersonRoleVolunteer['approved']) {
                $projectPersonRoleVolunteer['verified'] = true;
            }
        }
        //update user info
        $projectPerson->username = $this->filterScalarString($data,'userInfoUsername');

        //update form data
        $this->setData($projectPerson->toArray());

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
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regAge">Age:</label>
      <input name="age" type="text" class="col-xs-4 form-control" id="regAge" value="{$this->escape($personView->age)}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regPhone">Phone:</label>
      <input name="phone" type="text" class="col-xs-4 form-control" id="regPhone" value="{$this->escape($personView->phone)}">
    </div>
    <div class="form-group">
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
        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

        $certSH         = $personView->getCertClass('CERT_SAFE_HAVEN');
        $classSH        = ' '. (is_null($certSH) ? $personView->dangerClass : $certSH);

        $certConc       = $personView->getCertClass('CERT_CONCUSSION');
        $classConc      = ' '. (is_null($certConc) ? $personView->dangerClass : $certConc);
        
        $certBgCk       = $personView->getCertClass('CERT_BACKGROUND_CHECK');
        $classBgCk      = ' '. (is_null($certBgCk) ? '' : $certBgCk);

        $certRef        = $personView->getCertClass('CERT_REFEREE');
        $classCertRef   = ' '. (is_null($certRef) ? $personView->dangerClass : $certRef);

        $roleRef        = $personView->getRoles();
        $roleRef        = isset($roleRef['ROLE_REFEREE']) ? $roleRef['ROLE_REFEREE'] : null;
        $approvedRef    = isset($roleRef['approved']) ? (bool) $roleRef['approved'] : false;

        $roleRef        = !is_null($roleRef) ? $personView->getRoleClass($roleRef, $regYearProject) : null;
        $classRoleRef   = ' '. (is_null($roleRef) ? $personView->successClass : $roleRef);

        $roleVol        = $personView->getRoles();
        $roleVol        = isset($roleVol['ROLE_VOLUNTEER']) ? $roleVol['ROLE_VOLUNTEER'] : null;
        $approvedVol    = isset($roleVol['approved']) ? (bool) $roleVol['approved'] : false;

        $classRegYear   = ' ' . (in_array($personView->regYear, ['***','',null]) ? $personView->dangerClass : $personView->successClass);

        $sar = explode('/', $personView->orgKey);
        switch (count($sar)) {
            case 3:
                $section = $sar[0];
                $area = $sar[1];
                $region = ltrim($sar[2], '0');
                $state = '';
                break;
            case 4:
                $section = $sar[0];
                $area = $sar[1];
                $region = ltrim($sar[2], '0');
                $state = ltrim($sar[3], ' ');
                break;
            default:
                $section = '';
                $area = '';
                $region = '';
                $state = '';
        }

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update AYSO Information</h1>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userAYSOId">AYSO ID:</label>
      <input name="fedKeyId" type="text" class="col-xs-2 form-control" id="userAYSOId" value="{$this->escape($personView->fedKey)}">
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="regYear">Mem Year:</label>
      {$this->renderFormControlInput($this->formControls['regYear'],$this->escape($personView->regYear),'regYear','regYear','col-xs-2 form-control'.$classRegYear)}
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userRegion">AYSO Region:</label>
      <input name="orgKeyRegion" type="text" class="col-xs-3 form-control" id="userRegion" value="{$region}">
      <label class="col-xs-3 control-label control-text" for="userSAR"><span style="font-weight: bold">S/A/R/St: </span>{$personView->orgKey}</label>
    </div>
EOD;

        $certs = $personView->getCerts();
        if (isset($certs['CERT_REFEREE'])) {
            $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="badge">Referee:</label>
      {$this->renderFormControlInput($this->formControls['refereeBadge'],$this->escape($personView->refereeBadge),'badge','badge','col-xs-4 form-control'.$classCertRef)}
EOD;
        if ($this->willReferee($personView) != 'no'){
            $html .=
<<<EOD
      <label class="col-xs-3 control-label approved"><input name="approvedRef" value="approved" type="checkbox" {$this->isChecked($approvedRef) }> Approved to Referee</label>
EOD;
        }    
        $html .=
<<<EOD
    </div>
EOD;
        }
        $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userSH">Safe Haven:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->safeHavenCertified)),'userSH','userSH','col-xs-4 form-control'.$classSH)}      
EOD;
        if ($this->willVolunteer($personView) != 'no' or $this->willCoach($personView) != 'no'){
            $html .=
<<<EOD
      <label class="col-xs-3 control-label approved"><input name="approvedVol" value="approved" type="checkbox" {$this->isChecked($approvedVol) }> Approved to Volunteer</label>
EOD;
        }
        $html .=
<<<EOD
    </div>
EOD;

        $certs = $personView->getCerts();
        if (isset($certs['CERT_REFEREE'])) {
            $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userConc">Concussion:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->concussionTrained)),'userConc','userConc','col-xs-4 form-control'.$classConc)}
    </div>
EOD;
        }
//        $html .= <<<EOD
//    <div class="form-group">
//      <label class="col-xs-3 control-label" for="userBackground">FL BkGrnd Check:</label>
//      {$this->renderFormControlInput($this->formControls['YesNo'],strtolower($this->escape($personView->backgroundChecked)),'userBackground','userBackground','col-xs-4 form-control'.$classBgCk)}
//    </div>
//EOD;

        $html .= <<<EOD
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
      {$this->renderFormControlInput($this->formControls['YesNo'],$this->willReferee($personView),'willReferee','willReferee','col-xs-4 form-control')}
    </div>    
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willVolunteer">Will Volunteer:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],$this->willVolunteer($personView),'willVolunteer','willVolunteer','col-xs-4 form-control')}
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willCoach">Will Coach:</label>
      {$this->renderFormControlInput($this->formControls['YesNo'],$this->willCoach($personView),'willCoach','willCoach','col-xs-4 form-control')}
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
        $avail = isset($personView->person->avail);

        $availTue       = $avail ? strtolower($personView->availTue) == 'yes' : false;
        $availWed       = $avail ? strtolower($personView->availWed) == 'yes' : false;
        $availThu       = $avail ? strtolower($personView->availThu) == 'yes' : false;
        $availFri       = $avail ? strtolower($personView->availFri) == 'yes' : false;
        $availSatMorn   = $avail ? strtolower($personView->availSatMorn) == 'yes' : false;
        $availSatAfter  = $avail ? strtolower($personView->availSatAfter) == 'yes' : false;
        $availSunMorn   = $avail ? strtolower($personView->availSunMorn) == 'yes' : false;
        $availSunAfter  = $avail ? strtolower($personView->availSunAfter) == 'yes' : false;

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Availability Information</h1>
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availTue" type="checkbox" {$this->isChecked
        ($availTue)}>Available Tue (Soccerfest)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availWed" type="checkbox" {$this->isChecked($availWed)}>Available Wed (Pool Play)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availThu" type="checkbox" {$this->isChecked($availThu)}>Available Thu (Pool Play)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availFri" type="checkbox" {$this->isChecked($availFri)}>Available Fri (Pool Play)</label>
    </div>    
    <div class="form-group avail">
        <label class="col-xs-3 control-label"><input name="avail[]" value="availSatMorn" type="checkbox" 
        {$this->isChecked($availSatMorn)}>Available Sat Morning (QF)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSatAfter" type="checkbox" {$this->isChecked($availSatAfter)}>Available Sat Afternoon(SF)</label>
    </div>    
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunMorn" type="checkbox" 
      {$this->isChecked($availSunMorn)}>Available Sun Morning (FM)</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunAfter" type="checkbox" {$this->isChecked($availSunAfter)}>Available Sun Afternoon (FM)</label>
    </div>    
    <div class="form-group">
      <label class="col-xs-2 control-label" for="notes">Assignor Notes:</label>
      {$this->renderFormControl('notes')}
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
    <h1 class="panel-heading">Update User Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="userUname">User:</label>
      <input name="userInfoUsername" type="text" class="col-xs-4 form-control" id="userUname" value="{$this->escape($user['username'])}">
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;

    }
    private function isChecked(bool $value = null)
    {
        if (is_null($value)) return '';
        
        if($value) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }

        return $checked;
    }
    public function renderFormControl($key,$class="col-xs-4 form-control",$meta = null)
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
    public function renderFormControlInput($meta,$value,$id,$name,$class="form-control",$attrList='')
    {
        $type = $meta['type'];

        switch($type) {

            case 'select':
                return $this->renderFormControlInputSelect($meta['choices'],$value,$id,$name,$class,$attrList);

            case 'textarea':
                return $this->renderFormControlInputTextArea($meta,$value,$id,$name,$class,$attrList);

        }
        return $this->renderFormControlInputText($meta,$value,$id,$name,$class,$attrList);
    }
    private function renderFormControlInputText($meta,$value,$id,$name,$class,$attrList)
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $value = $this->escape($value);

        return  <<<EOD
<input 
  type="{$meta['type']} id="{$id}" class="{$class}" {$required}
  name="{$name}" value="{$value}" placeHolder="{$placeHolder}"} {$attrList}/>
EOD;
    }
    private function renderFormControlInputTextArea($meta,$value,$id,$name,$class,$attrList='')
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $rows = isset($meta['rows']) ? $meta['rows'] : 5;

        $value = $this->escape($value);

        return  <<<EOD
<textarea 
  id="{$id}" class="{$class}" rows="{$rows}" {$required}
  name="{$name}" placeHolder="{$placeHolder}"}  {$attrList}>{$value}</textarea>
EOD;
    }
    protected function renderFormControlInputSelect($choices,$value,$id,$name,$class,$attrList='')
    {
        $html = <<<EOD
<select id="{$id}" name="{$name}" class="{$class}"  {$attrList}>
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
    private function willReferee (ProjectPersonViewDecorator $personView)
    {
        $will= is_null($this->escape($personView->willReferee)) ? 'no' : $this->escape($personView->willReferee);

        return strtolower($will);
    }
    private function willCoach (ProjectPersonViewDecorator $personView)
    {
        $will = is_null($this->escape($personView->willCoach)) ? 'no' : $this->escape($personView->willCoach);

        return strtolower($will);
    }
    private function willVolunteer (ProjectPersonViewDecorator $personView)
    {
        $will = is_null($this->escape($personView->willVolunteer)) ? 'no' : $this->escape($personView->willVolunteer);

        return strtolower($will);
    }

}
