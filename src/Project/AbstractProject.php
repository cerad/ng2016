<?php declare(strict_types=0);

namespace Zayso\Project;

/**
 * @property-read string projectId
 * @property-read string abbv
 * @property-read string title
 * @property-read string desc
 *
 * @property-read string regYear
 * @property-read string timeZone
 * @property-read string rainedOutKey
 *
 * @property-read ProjectContact support
 *
 * Virtual
 * @property-read AbstractPageTemplate    pageTemplate
 * @property-read AbstractContentTemplate welcomeTemplate
 *
 */
abstract class AbstractProject //implements ProjectServiceInterface
{
    use ProjectServiceLocatorTrait;

    public $projectId;
    public $abbv;
    public $title;
    public $desc;

    public $regYear;

    public $support;

    // Local Data
    protected $projectData;
    protected $projectInfo;

    public function __construct(array $projectData)
    {
        $this->projectData = $projectData;
        $this->projectInfo = $projectData['info'];

        $info = $projectData['info'];
        $this->projectId = $info['key'];
        $this->abbv      = $info['abbv'];
        $this->title     = $info['title'];
        $this->desc      = $info['desc'];
        $this->regYear   = $info['regYear'];

        $contact = $info['support'];
        $this->support = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);

    }
    public function __get(string $name)
    {
        switch($name) {
            case 'pageTemplate':
                $templateServiceId = $this->projectInfo['pageTemplate'];
                return $this->projectServiceLocator->get($templateServiceId);

            case 'welcomeTemplate':
                $templateServiceId = $this->projectInfo['welcomeTemplate'];
                return $this->projectServiceLocator->get($templateServiceId);

            case 'timeZone':
                return $this->projectInfo['timeZone'];

            case 'rainedOutKey':
                return $this->projectInfo['rainedOutKey'];

        }
        return null;
    }
}