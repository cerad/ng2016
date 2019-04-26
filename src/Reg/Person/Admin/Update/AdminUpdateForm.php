<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Update;

use AppBundle\Action\Project\Person\ProjectPerson;

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
        //stash the request url
        //$projectPersonKey = $request->attributes->get('projectPersonKey');
        //$this->requestUrl = $this->generateUrl(
        //    $this->getCurrentRouteName(),
        //    ['projectPersonKey'=>$projectPersonKey]
        //);

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
        if (isset($projectPerson->roles['CERT_BACKGROUND_CHECK'])) {
            $projectPerson->roles['CERT_BACKGROUND_CHECK']['verified'] = $personData['userBackground'] === 'yes' ? true : null;            
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
        //$projectPerson->username = $this->filterScalarString($data,'userInfoUsername');

        //update form data
        $this->setData($projectPerson->toArray());

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
      <label class="col-xs-3 control-label" for="userAYSOId">AYSO ID:</label>
      <input name="fedKeyId" type="text" class="col-xs-2 form-control" id="userAYSOId" value="{$fedPersonId}">
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="regYear">Mem Year:</label>
      {$this->renderFormControlInputSelect(
            $this->formControls['regYear']['choices'],
            $person->regYear,
            'regYear','regYear','col-xs-2 form-control ' . $regYearClass)}
    </div>
    <div class="form-group">
      <label class="col-xs-3 control-label" for="userRegion">AYSO SAR:</label>
      <input name="orgKeyRegion" type="text" class="col-xs-3 form-control" id="userRegion" value="{$fedOrgId}">
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
          'userSH','userSH','col-xs-4 form-control ' . $certConcClass)}  
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
      <label class="col-xs-3 control-label"><input name="avail[]" value="availTue" type="checkbox" 
        {$avails['availTue'][1]}>{$avails['availTue'][0]}
      </label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availWed" type="checkbox" 
        {$avails['availWed'][1]}>{$avails['availWed'][0]}
      </label>
    </div>
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availThu" type="checkbox" 
        {$avails['availThu'][1]}>{$avails['availThu'][0]}</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availFri" type="checkbox" 
        {$avails['availFri'][1]}>{$avails['availFri'][0]}</label>
    </div>    
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSatMorn" type="checkbox" 
        {$avails['availSatMorn'][1]}>{$avails['availSatMorn'][0]}</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSatAfter" type="checkbox" 
        {$avails['availSatAfter'][1]}>{$avails['availSatAfter'][0]}</label>
    </div>    
    <div class="form-group avail">
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunMorn" type="checkbox" 
        {$avails['availSunMorn'][1]}>{$avails['availSunMorn'][0]}</label>
      <label class="col-xs-3 control-label"><input name="avail[]" value="availSunAfter" type="checkbox" 
        {$avails['availSunAfter'][1]}>{$avails['availSunAfter'][0]}</label>
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
