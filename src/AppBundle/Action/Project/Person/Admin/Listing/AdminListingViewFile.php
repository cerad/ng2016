<?php

namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\Person\Admin\AdminViewFilters;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminListingViewFile extends AbstractView
{
    /** var ProjectPersonViewDecorator **/
    private $personView;
    
    /** var AbstractExporter **/
    private $exporter;
    
    /** AdminViewFilters **/
    private $adminViewFilters;
    
    private $outFileName;
    
    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        AbstractExporter $exporter,
        AdminViewFilters $adminViewFilters
    )
    {
        $this->outFileName =  'RegisteredPeople' . '_' . date('Ymd_His') . '.' . $exporter->fileExtension;
     
        $this->personView = $projectPersonViewDecorator;
        $this->exporter = $exporter;
        $this->adminViewFilters = $adminViewFilters;
    }
    public function __invoke(Request $request)
    {
        $projectPersons = $request->attributes->get('projectPersons');

        $reportChoices = [
            'All'           =>  'All',
            'Referees'      =>  'Referees',
            'Volunteers'    =>  'Volunteers',
            'Unverified'    =>  'Unverified',
            'Unapproved'    =>  'Unapproved',
            'RefIssues'     =>  'Referees with Issues',
            'VolIssues'     =>  'Volunteers with Issues',
            'FL'            =>  'FL Residents'
        ];
        
        $content = [];
        foreach($reportChoices as $reportKey => $report) {
            $listPersons = $this->adminViewFilters->getPersonListByReport($projectPersons,$reportKey);

            $content = array_merge($content, $this->generateResponse($listPersons, $report));        
        }
        $content = $this->exporter->export($content);

        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', $this->exporter->contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }
    protected function generateResponse($projectPersons, $reportKey)
    {
        //set the header labels
        $data =   array(
            array ('AYSO ID','projectKey','personKey','Name','eMail','Phone','Age',
                   'Approved','Verified',
                   'MY','S/A/R/State','Certified Badge','Safe Haven','Concussion Aware',
                   'Shirt Size','Notes',
                   'Will Coach', 'Will Referee', 'Will Volunteer', 'User Notes', 'Notes'
            )
        );

        $personView = $this->personView;
        //set the data : game in each row
        foreach ( $projectPersons as $projectPerson ) {
            $personView->setProjectPerson($projectPerson);

            $data[] = array(
                $personView->fedId,
                $personView->projectKey,
                $personView->personKey,
                $personView->name,
                $personView->email,
                $personView->phone,
                $personView->age,
                $personView->approved,
                $personView->verified,
                $personView->regYear,
                $personView->sar,
                $personView->refereeBadge,
                $personView->safeHavenCertified,
                $personView->concussionTrained,
                $personView->shirtSize,
                $personView->notes,
                $personView->willCoach,
                $personView->willReferee,
                $personView->willVolunteer,
                $personView->notesUser,
                $personView->notes,
            );
        }
        
        $workbook[$reportKey]['data'] = $data;
        $workbook[$reportKey]['options']['hideCols'] = array('B','C');
        
        return $workbook;
    }
}
