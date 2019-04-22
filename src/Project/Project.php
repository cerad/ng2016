<?php declare(strict_types=0);

namespace Zayso\Project;

class Project implements ProjectInterface, CurrentProject, ProjectServiceInterface
{
    public $projectId;
    public $abbv;
    public $title;
    public $desc;

    public $regYear;

    // Contacts
    protected $_system;
    public    $support;
    public    $support2;
    public    $refAdmin;
    public    $refAssignor;
    protected $_gameScheduler;

    // Local Data
    protected $projectData;
    protected $projectInfo;

    protected $projectShowFlags;
    protected $projectServiceLocator;

    public function __construct(
        array $projectData,
        ProjectServiceLocator $projectServiceLocator,
        ProjectShowFlags      $projectShowFlags
)
    {
        $this->projectShowFlags      = $projectShowFlags;
        $this->projectServiceLocator = $projectServiceLocator;

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

        $contact = $info['support2'];
        $this->support2 = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);

        $contact = $info['administrator'];
        $this->refAdmin = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);

        $contact = $info['assignor'];
        $this->refAssignor = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);

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

                // Contacts
            case 'system':
                if ($this->_system) return $this->_system;
                $contact = $this->projectInfo['system'];
                $this->_system = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);
                return $this->_system;

            case 'gameScheduler':
                if ($this->_gameScheduler) return $this->_gameScheduler;
                $contact = $this->projectInfo['schedules'];
                return $this->_gameScheduler = new ProjectContact($contact['name'],$contact['email'],$contact['phone'],$contact['subject']);

                // Show Booleans
            case 'showHeaderImage':
                return $this->projectShowFlags->showHeaderImage;

            case 'showSchedulesMenu':
                return $this->projectShowFlags->showSchedulesMenu;

            case 'showResultsMenu':
                return $this->projectShowFlags->showResultsMenu;

            case 'showFinalResults':
                return $this->projectShowFlags->showFinalResults;
        }
        return null;
    }
}