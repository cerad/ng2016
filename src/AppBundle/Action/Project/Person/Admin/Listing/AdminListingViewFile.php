<?php

namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminListingViewFile extends AbstractView
{
    private $outFileName;
    private $exporter;

    private $personView;
    
    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        AbstractExporter $exporter
        )
    {
        $this->outFileName =  'RegisteredPeople' . '_' . date('Ymd_His') . '.' . $exporter->fileExtension;
     
        $this->personView = $projectPersonViewDecorator;
        $this->exporter = $exporter;
    }
    public function __invoke(Request $request)
    {
        $projectPersons = $request->attributes->get('projectPersons');

        $content = $this->generateResponse($projectPersons);

        $response = new Response();

        $options['hideCols'] = array('B','C');
        
        $response->setContent($this->exporter->export($content, $options));

        $response->headers->set('Content-Type', $this->exporter->contentType);

        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }
    protected function generateResponse($projectPersons)
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
//var_dump($this->personView);die();
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
        return $data;
    }
}
