<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer  as OrgKeyTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\VolunteerKeyTransformer as FedKeyTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;

class RegisterTemplateEmail extends AbstractView2
{
    private $fedKeyTransformer;
    private $orgKeyTransformer;
    private $phoneTransformer;

    public function __construct(
        FedKeyTransformer $fedKeyTransformer,
        OrgKeyTransformer $orgKeyTransformer,
        PhoneTransformer  $phoneTransformer
    )
    {
        $this->fedKeyTransformer = $fedKeyTransformer;
        $this->orgKeyTransformer = $orgKeyTransformer;
        $this->phoneTransformer  = $phoneTransformer;
    }

    public function renderHtml($person)
    {
        return <<<EOD
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=us-ascii">
  <link rel="stylesheet" type="text/css" href="http://ng2016register.zayso.org/bundles/ceradapp/css/ng.css" media="all">
  <title></title>
</head>
<body>
  <center>
    <div class="skHeader">
      <h1>
        <a href="http://www.aysonationalgames.org/" target="_blank">
          <img src="http://ng2016register.zayso.org/bundles/ceradapp/images/header-ipad_01.png" width="70%">
        </a>
      </h1>
    </div>
    <p class="skFont">AYSO WELCOMES YOU TO PALM BEACH COUNTY, FLORIDA, JULY 5-10, 2016</p>
    <div class="clear-both"></div>
  </center>
  <hr>
  <p>Thank you for registering to volunteer at the 2016 National Games!</p>
  {$this->renderHtmlPerson($person)}
  <p>
    As you might expect, we have a full calendar of soccer and related activities starting Tuesday July 5 and 
    running through Sunday July 10. 
    On Tuesday, July 5, 2016, we will have a mandatory meeting for Coaches and Referees at the 
    <a href="http://www3.hilton.com/en/hotels/florida/hilton-west-palm-beach-PBIWPHH/index.html" target="_blank">Hilton West Palm Beach</a> 
    to provide information to all coaches and referees on how to have a successful National Games. 
    We will review important roles, procedures, safety and more! 
    A full calendar may be viewed at 
      <a href="http://www.aysonationalgames.org/Default.aspx?tabid=730883" target="_blank">National Games Calendar</a>.
    </p>
    <p>
      General information about the Games can be found at 
      <a href="http://www.aysonationalgames.org/" target="_blank">National Games</a>.
    </p>
    
    <p>
      I will provide additional updates in the coming weeks. 
      Please drop me a note if you have any questions about officiating at the Games or with suggestions you have on how we can 
      better communicate the information you need.
    </p>

    <p>I look forward to meeting you at the Games.</p>

    <p>Sincerely,</p>
    
    <p>Tom Bobadilla<br>
      National Referee Administrator<br>
      2016 National Games Referee Administrator<br>
      <a href="mailto:ThomasBobadilla@ayso.org?subject=Question%20about%20the%202016%20National%Games">ThomasBobadilla@ayso.org</a>
    </p>
</body>
</html>
EOD;
    }
    private function renderHtmlPerson($person)
    {
        $plans = isset($person['plans'])   ? $person['plans'] : null;

        $willAttend    = isset($plans['willAttend'])    ? $plans['willAttend']    : 'Unknown';
        $willReferee   = isset($plans['willReferee'])   ? $plans['willReferee']   : 'No';
        //$willVolunteer = isset($plans['willVolunteer']) ? $plans['willVolunteer'] : 'No';

        // Should have transformers
        $willAttend    = ucfirst($willAttend);
        $willReferee   = ucfirst($willReferee);
        //$willVolunteer = ucfirst($willVolunteer);

        $badge = isset($person['roles']['ROLE_REFEREE']) ?
            $person['roles']['ROLE_REFEREE']['badge'] :
            null;

        if ($willReferee != 'No' && $badge) {
            $willReferee = sprintf('%s (%s)',$willReferee,$badge);
        }
        return <<<EOD
<table border="1">
  <tr><td>Name         </td><td>{$person['name']}</td></tr>
  <tr><td>Email        </td><td>{$person['email']}</td></tr>
  <tr><td>Phone        </td><td>{$this->phoneTransformer->transform($person['phone'])}</td></tr>
  <tr><td>Will Attend  </td><td>{$willAttend}</td></tr>
  <tr><td>Will Referee </td><td>{$willReferee}</td></tr>
  <tr><td>AYSO ID      </td><td>{$this->fedKeyTransformer->transform($person['fedKey'])}</td></tr>
  <tr><td>Mem Year     </td><td>{$person['regYear']}</td></tr>
  <tr><td>Referee Badge</td><td>{$badge}</td></tr>
  <tr><td>SAR          </td><td>{$this->orgKeyTransformer->transform($person['orgKey'])}</td></tr>
</table>
EOD;
    }
}