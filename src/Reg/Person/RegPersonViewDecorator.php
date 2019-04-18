<?php /** @noinspection PhpUndefinedFieldInspection */
declare(strict_types=1);

namespace Zayso\Reg\Person;

use Zayso\Common\DataTransformer\PhoneTransformer;
use Zayso\Fed\Ayso\AysoIdTransformer;
use Zayso\Fed\Ayso\AysoOrgViewTransformer;

/**
 * @property-read RegPerson person
 * @property-read int       regPersonId
 *
 * @property-read string name
 * @property-read string email
 * @property-read string phone
 *
 * @property-read string fedId
 * @property-read string orgId
 * @property-read string orgKey
 *
 * @property-read bool willCoach
 * @property-read bool willReferee
 * @property-read bool willVolunteer
 *
 * @property-read bool isReferee
 *
 * @property-read string willRefereeBadge
 *
 * @property-read string notesUser
 *
 * @property-read string availTue
 * @property-read string availWed
 * @property-read string availThu
 * @property-read string availFri
 * @property-read string availSatMorn
 * @property-read string availSatAfter
 * @property-read string availSunMorn
 * @property-read string availSunAfter
 */
final class RegPersonViewDecorator
{
    public $person;

    private $phoneTransformer;
    private $fedIdTransformer;
    private $fedOrgViewTransformer;
    private $willRefereeViewTransformer;

    public function __construct(
        PhoneTransformer       $phoneTransformer,
        AysoIdTransformer       $fedIdTransformer,
        AysoOrgViewTransformer  $fedOrgViewTransformer,
        RegPersonWillRefereeViewTransformer $willRefereeViewTransformer
    )
    {
        $this->phoneTransformer      = $phoneTransformer;
        $this->fedIdTransformer      = $fedIdTransformer;
        $this->fedOrgViewTransformer = $fedOrgViewTransformer;
        $this->willRefereeViewTransformer = $willRefereeViewTransformer;
    }
    public function setPerson(RegPerson $person)
    {
        $this->person = $person;
    }
    //public function getKey()
    //{
    //    return $this->person->getKey(); // Added this but don't think want to use it
    //}
    public function getRoles() {
        return $this->person->getRoles();
    }
    public function getCerts() {
        return $this->person->getCerts();
    }
    //private $infoClass    = 'bg-info';
    public $dangerClass  = 'bg-danger';
    public $warningClass = 'bg-warning';
    public $successClass = 'bg-success';

    //public $infoStyle    = 'background-color: #d9edf7';
    public $dangerStyle  = 'background-color: #f2dede';
    public $warningStyle = 'background-color: #fcf8e3';
    public $successStyle = 'background-color: #dff0d8';
    // Don't really like having to use methods here
    public function getRegYearClass($regYearProject)
    {
        return $this->person->regYear >= $regYearProject ? $this->successClass : $this->dangerClass;
    }
    public function getRegYearStyle($regYearProject)
    {
        return $this->person->regYear >= $regYearProject ? $this->successStyle : $this->dangerStyle;
    }
    public function getRegYear($regYearProject)
    {
        return $this->person->regYear >= $regYearProject ? $this->person->regYear : $this->regYear . ' ***';
    }
    public function getOrgKeyClass()
    {
        $sar = $this->orgKey;
        return ($sar && substr($sar,0,1) !== 'A') ? $this->successClass : $this->dangerClass;
    }
    public function getOrgKeyStyle()
    {
        $sar = $this->orgKey;
        return ($sar && substr($sar,0,1) !== 'A') ? $this->successStyle : $this->dangerStyle;
    }
    public function getCertClass($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        return $this->person->getCert($certKey)->verified ? $this->successClass : $this->dangerClass;
    }
    public function getCertStyle($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        return $this->person->getCert($certKey)->verified ? $this->successStyle : $this->dangerStyle;
    }
    public function getCertBadge($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        $cert = $this->person->getCert($certKey);
        $suffix = $cert->verified ? null : ' ***';
        if ($certKey !== 'CERT_REFEREE') {
            return $cert->verified ? 'Yes' : 'No' . $suffix;
        }

        if ((!$cert->badgeUser) || strpos($cert->badge, $cert->badgeUser) > -1) {
            return $cert->badge . $suffix;
        }

        return $cert->badge . '/' . $cert->badgeUser . $suffix;
    }
    public function hasCertIssues()
    {
        $certs = $this->getCerts();
        foreach($certs as $cert){
            if ( !$cert->verified) {
                return true;
            }
        }
        return false;
    }
    public function getRoleClass($role)
    {
        if ($role->approved) {
            return $this->successClass;
        }
        return (!$this->hasCertIssues()) ? $this->warningClass : $this->dangerClass;
    }
    public function getRoleStyle($role)
    {
        if ($role->approved) {
            return $this->successStyle;
        } 
        return (!$this->hasCertIssues()) ? $this->warningStyle : $this->dangerStyle;
    }
    public function __get($name)
    {
        $person = $this->person;
        
        switch($name) {
            
            case 'approved':
                $role = $person->getRole('ROLE_REFEREE');
                return $role ? ($role['approved'] ? 'Yes': '') : null;
                
            case 'verified':
                $role = $person->getRole('ROLE_REFEREE');
                return $role ? ($role['verified'] ? 'Yes' : '') : null;
                
            case 'phone':  
                return $this->phoneTransformer->transform($person->phone);

            case 'regPersonId': return $person->regPersonId;

            case 'fedId':
            case 'fedKey':
                return $this->fedIdTransformer->transform($person->fedPersonId);
            
            case 'sar':
            case 'orgKey': 
                return $this->fedOrgViewTransformer->transform($person->fedOrgId);

            case 'personKey':
                return $person->personId;
            
            case 'refereeBadge':
                $role = $person->getRole('CERT_REFEREE');
                return $role ? $role->badge : null;
            
            case 'refereeBadgeUser':
                $role = $person->getRole('CERT_REFEREE');
                return $role ? $role->badgeUser : null;

            case 'safeHavenCertified':
                $role = $person->getRole('CERT_SAFE_HAVEN');
                if (!$role) return null;
                switch(strtolower($role->verified)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                        return null;
                }
                return 'Yes';

            case 'concussionAware':
            case 'concussionTrained':
            case 'concussionCertified':
                $role = $person->getRole('CERT_CONCUSSION');
                if (!$role) {
                    return null;
                }
                if ($role->verified) {
                    return 'Yes';
                }
                switch(strtolower($role->verified)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                        return null;
                }
                return 'Yes';

            case 'backgroundChecked':
            case 'floridaResident':
                $role = $person->getRole('CERT_BACKGROUND_CHECK');
                if (!$role) {
                    return 'nr';
                }
                if ($role->verified) {
                    return 'Yes';
                }
                switch(strtolower($role->verified)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                        return null;
                }
                return 'Yes';

            case 'isReferee': return $person->isReferee;

            case 'willCoach':
            case 'willAttend':
            case 'willReferee':
            case 'willVolunteer':
                $will = isset($person->plans[$name]) ? $person->plans[$name] : null;
                return ucfirst(strtolower($will));
            
            case 'willRefereeBadge':
                $willRefereeViewTransformer = $this->willRefereeViewTransformer;
                return $willRefereeViewTransformer($person);

            case 'availTue':
            case 'availWed':
            case 'availThu':
            case 'availFri':
            case 'availSatMorn':
            case 'availSatAfter':
            case 'availSunMorn':
            case 'availSunAfter':
                $will = isset($person->avail[$name]) ? $person->avail[$name] : null;
                return ucfirst(strtolower($will));
            
            case 'shirtSize':
                $size = strtolower($person->shirtSize);
                switch($size) {
                    case 'youths':    return 'Youth S';
                    case 'youthm':    return 'Youth M';
                    case 'youthl':    return 'Youth L';
                    case 'adults':    return 'Adult S';
                    case 'adultm':    return 'Adult M';
                    case 'adultl':    return 'Adult L';
                    case 'adultlx':   return 'Adult XL';
                    case 'adultlxx':  return 'Adult XXL';
                    case 'adultlxxx': return 'Adult XXXL';
                }
                return 'na';
            
            case 'notes':
                return $person->notes;
            
            case 'notesUser':
                return $person->notesUser;
            
        }
        return $person->$name; // Don't like this at all!
    }
}
