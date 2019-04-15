<?php declare(strict_types=0);

namespace Zayso\Project;

/**
 * @property-read string projectId
 * @property-read string abbv
 * @property-read string title
 * @property-read string desc
 *
 * @property-read string regYear
 *
 * @property-read ProjectContact support
 *
 * Virtual
 * @property-read AbstractPageTemplate pageTemplate
 *
 */
abstract class AbstractProject //implements ProjectServiceInterface
{
    public $projectId;
    public $abbv;
    public $title;
    public $desc;

    public $regYear;

    public $support;

    // Local Data
    protected $projectData;
    protected $projectInfo;

    /** @var ProjectServiceLocator  */
    protected $projectServiceLocator;

    /** @required */
    public function setOnceProjectServiceLocator(ProjectServiceLocator $projectServiceLocator) : void
    {
        $this->projectServiceLocator = $this->projectServiceLocator ? $this->projectServiceLocator : $projectServiceLocator;
    }
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
                $pageTemplateServiceId = $this->projectInfo['pageTemplate'];
                return $this->projectServiceLocator->get($pageTemplateServiceId);
        }
        return null;
    }
}