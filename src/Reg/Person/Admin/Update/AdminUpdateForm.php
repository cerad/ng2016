<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Update;

use Symfony\Component\HttpFoundation\Request;
use Zayso\Common\Traits\FormTrait;
use Zayso\Common\Traits\RequestTrait;
use Zayso\Reg\Person\RegPerson;

/*
 * The user registration form just listed the various inputs
 * It operated on array data
 * Fairly compact and basically driven by the project yaml file
 *
 * This admin form is much prettier and much more custom designed
 * It could operate directly on the reg person object
 *
 * For generating the form a PersonView could be used to do all the transforming stuff
 * For loading the form back in, updating the object directly will require access to the transformers
 * Could still take advantage of the form controls meta data
 *
 * It is not just the transformer, the person view class also has styling information and such
 */
class AdminUpdateForm
{
    use FormTrait;
    use RequestTrait;

    public $formControls;

    /** @var RegPerson */
    private $person;

    public function setRegPerson(RegPerson $person)
    {
        $this->person = $person;
    }
    public function __construct(
        array $projectControls,
        array $formControls
    )
    {
        foreach($formControls as $key => $meta)
        {
            if (!isset($meta['type'])) {
                $meta = array_merge($meta,$projectControls[$key]);
            }
            $formControls[$key] = $meta;
        }
        // Need to tack on a few because admin form is different
        $formControls['notes']              = $projectControls['notes'];
        $formControls['regYear']            = $projectControls['regYear'];
        $formControls['safeHavenCertified'] = $projectControls['yesNo'];
        $formControls['concussionTrained']  = $projectControls['yesNo'];

        $this->formControls = $formControls;
    }
    public function handleRequest(Request $request) : void
    {
        //check for post action
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;
    
        $errors = [];
        $person = $this->person;

        //get the form data
        $data = $request->request->all(); dump($data);

        //update person array with form data
        $personData = [
            'name'      => $this->filterScalarString ($data,'name'),
            'email'     => $this->filterScalarString ($data,'email'),
            'age'       => $this->filterScalarInteger($data,'age'),
            'phone'     => $this->filterScalarString ($data,'phone'),
            'shirtSize' => $this->filterScalarString ($data,'shirtSize'), // TODO Validate these
            'regYear'   => $this->filterScalarString ($data,'regYear'),
            'notes'     => $this->filterScalarString ($data,'notes'),
            'notesUser' => $this->filterScalarString ($data,'notesUser'),
        ];
        $fedIdTransformer = $this->getTransformer('aysoid_transformer');
        $personData['fedPersonId'] = $fedIdTransformer->transform($this->filterScalarString ($data,'fedPersonId'));
        //$personData['fedOrgId']    = $fedIdTransformer->transform($this->filterScalarString ($data,'fedOrgId'));

        $person->setFromArray($personData);

        // Update plans
        $plans = $person->plans;

        $plans['willReferee']   = $this->filterScalarString ($data,'willReferee');
        $plans['willVolunteer'] = $this->filterScalarString ($data,'willVolunteer');
        $plans['willCoach']     = $this->filterScalarString ($data,'willCoach');

        $person->set('plans',$plans);

        // Update availability
        $personAvail = $person->avail;

        foreach($data['avail'] as $key => $value) {
            $personAvail[$key] = $value;
        }
        $person->set('avail',$personAvail);

        // A concussion element is only displayed for those with referee certs
        if (isset($data['userConc'])) {
            $verified = $data['userConc'] === 'yes' ? true : false;
            $cert = $person->getCert('CERT_CONCUSSION',true);
            $cert->set('verified',$verified);
        }
        // Safe haven is always displayed but check for consistency
        if (isset($data['userSH'])) {
            $verified = $data['userSH'] === 'yes' ? true : false;
            $cert = $person->getCert('CERT_SAFE_HAVEN',true);
            $cert->set('verified',$verified);
        }

        // Referee is a little bit trickier, create a cert if will referee is set by admin
        if ($person->willReferee) {
            $person->getCert('CERT_REFEREE',true); // Just creates if we need one
            $person->getCert('CERT_SAFE_HAVEN',true); // Just creates if we need one
            $person->getCert('CERT_CONCUSSION',true); // Just creates if we need one
        }
        if (isset($data['refereeBadge'])) {
            $certRef = $person->getCert('CERT_REFEREE',true);
            $certRef->set('badge',$data['refereeBadge']);
        }
        // If approved to referee then update role
        if (isset($data['approvedRef'])) {
            $role = $person->getRole('ROLE_REFEREE',true);
            $role->set('approved',true);
            $cert = $person->getCert('CERT_REFEREE');
            if ($cert) $cert->set('verified',true);
        }
        else {
            // Un approve
            $role = $person->getRole('ROLE_REFEREE');
            if ($role) $role->set('approved',false);
        }
        // Same for volunteer
        if (isset($data['approvedVol'])) {
            $role = $person->getRole('ROLE_VOLUNTEER',true);
            $role->set('approved',true);
            $cert = $person->getCert('CERT_VOLUNTEER');
            if ($cert) $cert->set('verified',true);
        }
        else {
            // Un approve
            $role = $person->getRole('ROLE_VOLUNTEER');
            if ($role) $role->set('approved',false);
        }
        $this->formDataErrors = $errors;
    }
    public function render() : string
    {
        $html = <<<EOD
{$this->renderFormErrors()}

{$this->renderForm($this->person)}

EOD;
        return $html;
    }
    private function renderForm(RegPerson $person) : string
    {
        $csrfToken = 'TODO';
        $requestUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['regPersonId' => $person->regPersonId]
        );
        $html = <<<EOD
<form method="post" action="{$requestUrl}" class="form-horizontal">
    <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
    {$this->renderRegistrationInfo($person)}
    {$this->renderAYSOInfo        ($person)}
    {$this->renderPlansInfo       ($person)}
    {$this->renderAvailInfo       ($person)}
</form>
EOD;
        return $html;
    }

    private function renderRegistrationInfo(RegPerson $person) : string
    {
        $name  = $this->escape($person->name);
        $email = $this->escape($person->email);
        $age   = $this->escape((string)$person->age);

        $phoneTransformer = $this->getTransformer('phone_transformer');
        $phone = $phoneTransformer->transform($person->phone);

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Registration Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regName">Name:</label>
      <input name="name" type="text" class="col-xs-4 form-control" id="regName" value="{$name}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regEmail">Email:</label>
      <input name="email" type="text" class="col-xs-4 form-control" id="regEmail" value="{$email}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regAge">Age:</label>
      <input name="age" type="text" class="col-xs-4 form-control" id="regAge" value="{$age}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="regPhone">Phone:</label>
      <input name="phone" type="text" class="col-xs-4 form-control" id="regPhone" value="{$phone}">
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="shirtSize">Shirt Size:</label>
      {$this->renderFormControlInputSelect(
          $this->formControls['shirtSize']['choices'],
          $person->shirtSize,
          'shirtSize','shirtSize','col-xs-4 form-control')}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;

        return $html;
    }
    private function renderAysoInfo(RegPerson $person) : string
    {
        $dangerClass  = 'bg-danger';
        $successClass = 'bg-success';

        $fedIdTransformer = $this->getTransformer('aysoid_transformer');
        $fedPersonId = $fedIdTransformer->transform($person->fedPersonId);
        $fedOrgId    = $fedIdTransformer->transform($person->fedOrgId);

        $certSH          = $person->getCert('CERT_SAFE_HAVEN');
        $certSHClass     = $certSH && $certSH->verified ? $successClass : $dangerClass;
        $certSHCertified = $certSH && $certSH->verified ? 'yes' : 'no';

        $certConc          = $person->getCert('CERT_CONCUSSION');
        $certConcClass     = $certConc && $certConc->verified ? $successClass : $dangerClass;
        $certConcCertified = $certConc && $certConc->verified ? 'yes' : 'no';

        $certRef      = $person->getCert('CERT_REFEREE');
        $certRefClass = $certRef && $certRef->verified ? $successClass : $dangerClass;

        $roleRef     = $person->getRole('ROLE_REFEREE');
        $approvedRef = $roleRef && (bool) $roleRef->approved ? 'checked="checked"' : '';

        $roleVol     = $person->getRole('ROLE_VOLUNTEER');
        $approvedVol = $roleVol && $roleVol->approved ? 'checked="checked"' : '';

        $regYearClass = $person->regYear ? $successClass : $dangerClass;
/*
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
*/
        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update AYSO Information</h1>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="fedPersonId">AYSO ID:</label>
      <input name="fedPersonId" type="text" class="col-xs-2 form-control" id="fedPersonId" value="{$fedPersonId}">
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="regYear">Mem Year:</label>
      {$this->renderFormControlInputSelect(
            $this->formControls['regYear']['choices'],
            $person->regYear,
            'regYear','regYear','col-xs-2 form-control ' . $regYearClass)}
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="fedOrgId">AYSO SAR:</label>
      <input name="fedOrgId" type="text" class="col-xs-3 form-control" id="fedOrgId" value="{$fedOrgId}">
      <label class="col-xs-3 control-label control-text" for="userSAR"><span style="font-weight: bold">S/A/R/St: </span>{TODO}</label>
    </div>
EOD;
        if ($certRef) {
            $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="badge">Referee Badge:</label>
      {$this->renderFormControlInputSelect(
                $this->formControls['refereeBadge']['choices'],
                $certRef->badge,
                'refereeBadge','refereeBadge','col-xs-4 form-control ' . $certRefClass)}
EOD;
        if ($person->willReferee){
            $html .= <<<EOD
      <label class="col-xs-3 control-label approved"><input name="approvedRef" value="approved" type="checkbox" 
        {$approvedRef}> Approved to Referee</label>
EOD;
        }
        $html .= <<<EOD
    </div>
EOD;
        }

        $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userSH">Safe Haven:</label>
      {$this->renderFormControlInputSelect(
            $this->formControls['safeHavenCertified']['choices'],
            $certSHCertified,
            'userSH','userSH','col-xs-4 form-control ' . $certSHClass)}      
EOD;
        if ($person->willVolunteer or $person->willCoach){
            $html .= <<<EOD
      <label class="col-xs-3 control-label approved"><input name="approvedVol" value="approved" type="checkbox" 
        {$approvedVol}> Approved to Volunteer</label>
EOD;
        }
        $html .= <<<EOD
    </div>
EOD;
        if ($certRef) {
            $html .= <<<EOD
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userConc">Concussion:</label>
      {$this->renderFormControlInputSelect(
          $this->formControls['concussionTrained']['choices'], 
          $certConcCertified, 
          'userConc','userConc','col-xs-4 form-control ' . $certConcClass)}  
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;
        }
        return $html;
    }
    private function renderPlansInfo(RegPerson $person)
    {
        $plans = $person->plans;

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Plans Information</h1>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willReferee">Will Referee:</label>
      {$this->renderFormControlInputSelect(
            $this->formControls['willReferee']['choices'],
            $plans['willReferee'],
            'willReferee','willReferee','col-xs-4 form-control')} 
    </div>    
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willVolunteer">Will Volunteer:</label>
      {$this->renderFormControlInputSelect(
          $this->formControls['willVolunteer']['choices'], 
          $plans['willVolunteer'],
            'willVolunteer','willVolunteer','col-xs-4 form-control')}
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="willCoach">Will Coach:</label>
            {$this->renderFormControlInputSelect(
            $this->formControls['willCoach']['choices'],
            $plans['willCoach'],
            'willCoach','willCoach','col-xs-4 form-control')}
    </div>
    <div class="form-group">
      <label class="col-xs-2 control-label" for="notesUser">User Notes:</label>
      {$this->renderFormControlInputTextArea($this->formControls['notesUser'],$person->notesUser,'notesUser','notesUser')}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;
        return $html;
    }
    // Not sure I like the idea of reducing this to a checkbox
    private function renderAvailInfo(RegPerson $person)
    {
        $personAvail = $person->avail;

        $avails = [];
        foreach($this->formControls as $key => $control) {
            if (isset($control['group']) && $control['group'] === 'avail') {
                $checked = (isset($personAvail[$key]) && strtolower($personAvail[$key]) === 'yes') ? 'checked="checked"' : '';
                $avails[$key] = [$control['label'],$checked];
            }
        }

        $html = <<<EOD
<div class="panel panel-default">
    <h1 class="panel-heading">Update Availability Information</h1>
    <div class="form-group avail">
      {$this->renderAvailElement($personAvail, 'availTue')}
      {$this->renderAvailElement($personAvail, 'availWed')}
    </div>
    <div class="form-group avail">
      {$this->renderAvailElement($personAvail, 'availThu')}
      {$this->renderAvailElement($personAvail, 'availFri')}
    </div>    
    <div class="form-group avail">
      {$this->renderAvailElement($personAvail, 'availSatMorn')}
      {$this->renderAvailElement($personAvail, 'availSatAfter')}
    </div>    
    <div class="form-group avail">
      {$this->renderAvailElement($personAvail, 'availSunMorn')}
      {$this->renderAvailElement($personAvail, 'availSunAfter')}
    </div>    
    <div class="form-group">
      <label class="col-xs-2 control-label" for="notes">Assignor Notes:</label>
      {$this->renderFormControlInputTextArea($this->formControls['notes'],$person->notes,'notes','notes')}
    </div>
    {$this->renderPanelFooter()}
</div>
EOD;
        return $html;
    }
    private function renderAvailElement(array $personAvail, string $key) : string
    {
        $label = $this->formControls[$key]['label'];

        $checked = (isset($personAvail[$key]) && strtolower($personAvail[$key]) === 'yes') ? 'checked="checked"' : '';

        return <<<EOT
      <input type="hidden" name="avail[{$key}]" value="no" />
      <label class="col-xs-3 control-label">
        <input name="avail[{$key}]" value="yes" type="checkbox" {$checked} />
        {$label}
      </label>
EOT;
    }
    private function renderPanelFooter() : string
    {
        $html = <<<EOD
    <div class="panel-footer clearfix">
        <div class="pull-right">
            <a href="{$this->generateUrl('reg_person_admin_listing')}" name="cancel" class="btn btn-sm btn-default ">Cancel</a>
            <button type="submit" name="save" class="btn btn-sm btn-default submit">
              <span class="glyphicon glyphicon-ok"></span> Save & Continue
            </button>
            <button type="submit" name="saveAndReturn" class="btn btn-sm btn-primary submit">
              <span class="glyphicon glyphicon-ok"></span> Save & Return to Listing
            </button>
        </div>
    </div>        
EOD;
        return $html;
    }
    /* ===================================================
     * This was never actually implemented
     * Should have allowed resetting a password or making an user inactive
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
    }*/

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
        //$label = isset($meta['label']) ? $this->escape($meta['label']) : null;
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
  type="{$meta['type']}" id="{$id}" class="{$class}" {$required}
  name="{$name}" value="{$value}" placeHolder="{$placeHolder}" {$attrList}/>
EOD;
    }
    private function renderFormControlInputTextArea($meta,$value,$id,$name,$class='col-xs-4 form-control',$attrList='')
    {
        $required = (isset($meta['required']) && $meta['required']) ? 'required' : null;

        $placeHolder = isset($meta['placeHolder']) ? $this->escape($meta['placeHolder']) : null;

        $rows = isset($meta['rows']) ? $meta['rows'] : 5;

        $value = $this->escape($value);

        return  <<<EOD
<textarea 
  id="{$id}" class="{$class}" rows="{$rows}" {$required}
  name="{$name}" placeHolder="{$placeHolder}"  {$attrList}>{$value}</textarea>
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
  <option value="{$choiceValue}" {$selected}>{$this->escape($choiceContent)}</option>
  
EOD;
        }
        $html .= <<<EOD
</select>
EOD;
        return $html;
    }
}
