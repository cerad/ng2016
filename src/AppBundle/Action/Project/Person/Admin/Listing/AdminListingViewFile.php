<?php

namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\Person\Admin\AdminViewFilters;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet;

class AdminListingViewFile extends AbstractView
{
    /** var ProjectPersonViewDecorator **/
    private $personView;
    
    /** var AbstractExporter **/
    private $exporter;
    
    /** AdminViewFilters **/
    private $adminViewFilters;
    
    private $outFileName;

    private $regYearProject;
    
    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        AbstractExporter $exporter,
        AdminViewFilters $adminViewFilters,
        $project
    )
    {
        $this->outFileName =  'RegisteredPeople' . '_' . date('Ymd_His') . '.' . $exporter->fileExtension;
     
        $this->personView = $projectPersonViewDecorator;
        $this->exporter = $exporter;
        $this->adminViewFilters = $adminViewFilters;
        $this->regYearProject = $project['info']['regYear'];
    }

    /**
     * @param Request $request
     * @return Response
     * @throws PhpSpreadsheet\Exception
     * @throws PhpSpreadsheet\Writer\Exception
     */
    public function __invoke(Request $request)
    {
        $reportKey = $request->attributes->get('reportKey');

        $projectPersons = $request->attributes->get('projectPersons');
        $exportAll = !is_bool(strpos($request->getQueryString(), 'all'));

        $reportChoices = [
            'All'           =>  'All',
            'AvailableReferees'      =>  'Available Referees',
            'Unverified'    =>  'Unverified',
            'Unapproved'    =>  'Unapproved',
            'RefCertIssues' => 'Referees with Cert Issues',
            'RefIssues'     =>  'Referees with Issues',
            'RefCertConflicts' => 'Referees with Cert Conflicts',
//            'AdultRefs'     =>  'Referees with Adult Experience',
            'AvailableVolunteers'    =>  'Available Volunteers',
            'VolIssues'     =>  'Volunteers with Issues',
        ];


        $reportKey = $exportAll ? 'All' : $reportKey;

        $listPersons = $this->adminViewFilters->getPersonListByReport($projectPersons, $this->regYearProject, $reportKey);
        $report = is_null($reportKey) ? 'All' : $reportChoices[$reportKey];

        $content = $this->generateResponse($listPersons, $report);

        $this->outFileName = str_replace(' ', '_', $report) . '.' . $this->outFileName;

        $content = $this->exporter->export($content);

        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', $this->exporter->contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }

    /**
     * @param $projectPersons
     * @param $reportKey
     * @return mixed
     */
    protected function generateResponse($projectPersons, $reportKey)
    {

        $yes = ['Yes'];
        $yesMaybe = ['Yes', 'Maybe'];

        //set the header labels
        $data =   array(
            array ('AYSO ID','Name','eMail','Phone','Age',
                   'Approved','Verified', 'Created On',
                   'MY','S/A/R/State','Certified Badge','Safe Haven','Concussion Aware',
                   'Shirt Size','Notes',
                   'Will Coach', 'Will Referee', 'Will Volunteer',
                    'Avail Fri', 'Avail Sat AM', 'Avail Sat PM','Avail Sun AM', 'Avail Sun PM',
                    'User Notes', 'Notes'
            )
        );

        $personView = $this->personView;
        //set the data : game in each row
        foreach ( $projectPersons as $projectPerson ) {
            $personView->setProjectPerson($projectPerson);

            $approved = !(
                (in_array($personView->willReferee, $yesMaybe) && !$personView->approvedRef) ||
                (in_array($personView->willVolunteer, $yesMaybe) && !$personView->approvedVol)
                );

                $data[] = array(
                $personView->fedId,
                $personView->name,
                $personView->email,
                $personView->phone,
                $personView->age,
                $approved ? 'Yes' : 'No',
                $personView->verified ? 'Yes' : 'No',
                $personView->createdOn,
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
                $personView->availFri,
                $personView->availSatMorn,
                $personView->availSatAfter,
                $personView->availSunMorn,
                $personView->availSunAfter,
                $personView->notesUser,
                $personView->notes,
            );
        }
        $workbook[$reportKey]['data'] = $data;
        $workbook[$reportKey]['options']['freezePane'] = 'A2';

        return $workbook;
    }
}
