<?php declare(strict_types=1);

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
 */
abstract class AbstractProject
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
}