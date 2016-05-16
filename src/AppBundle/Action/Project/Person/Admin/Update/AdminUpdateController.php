<?php
namespace AppBundle\Action\Project\Person\Admin\Update;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminUpdateController extends AbstractController
{
    /** @var  AdminUpdateForm */
    private $updateForm;

    /** @var  ProjectPersonRepositoryV2 */
    private $projectPersonRepository;
    
    private $requestUrl;
    
    public function __construct(
        ProjectPersonRepositoryV2 $projectPersonRepository,
        AdminUpdateForm $updateForm
    )
    {
        $this->updateForm = $updateForm;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request, $projectPersonKey)
    {
        //check for proper projectPersonKey
        $parts = explode('.',$projectPersonKey);
        
        if (count($parts) != 2) {
            return $this->redirectToRoute('project_person_admin_listing');
        }
        $projectPerson = $this->projectPersonRepository->find($parts[0],$parts[1]);
        if (!$projectPerson) {
            return $this->redirectToRoute('project_person_admin_listing');
        }

        //save the request url
        $requestUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['projectPersonKey' => $projectPersonKey]
        );
        
        //add the projectPerson to the request
        $request->attributes->set('projectPerson', $projectPerson);
        
        //initialize the update form with the person data as array
        $updateForm = $this->updateForm;
        $updateForm->setData($projectPerson->toArray());

        //check for post or not
        $updateForm->handleRequest($request);

        if ($updateForm->isValid()) {
            // if post
            // get the data from the form
            $projectPersonArray = $updateForm->getData();

            // convert to object
            $projectPerson = $projectPerson->fromArray($projectPersonArray);

            // Save the data
            $this->projectPersonRepository->save($projectPerson);

            // respond to save & continue
            if ($request->request->has('save')) {
                return $this->redirect($requestUrl);
            }

            //respond to saveAndReturn
            return $this->redirectToRoute('project_person_admin_listing');
        }
        
        return null;
    }
}
