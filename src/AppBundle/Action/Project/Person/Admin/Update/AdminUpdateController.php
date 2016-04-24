<?php
namespace AppBundle\Action\Project\Person\Admin\Update;

use AppBundle\Action\AbstractController;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use Symfony\Component\HttpFoundation\Request;

class AdminUpdateController extends AbstractController
{
    private $updateForm;
    private $projectPersonRepository;
    
    public function __construct(
        ProjectPersonRepositoryV2 $projectPersonRepository,
        AdminUpdateForm $updateForm
    )
    {
        $this->updateForm = $updateForm;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function __invoke(Request $request,$projectPersonKey)
    {
        $parts = explode('.',$projectPersonKey);
        
        if (count($parts) != 2) {
            return $this->redirectToRoute('project_person_admin_listing');
        }
        $projectPerson = $this->projectPersonRepository->find($parts[0],$parts[1]);
        if (!$projectPerson) {
            return $this->redirectToRoute('project_person_admin_listing');
        }
        
        $updateForm = $this->updateForm;
        
        $updateForm->setData($projectPerson->toArray());
        
        $updateForm->handleRequest($request);
        
        if ($updateForm->isValid()) {

            $projectPersonArray = $updateForm->getData();

            //return $this->redirectToRoute('project_person_admin_update',[]);
        }
        
        return null;
    }
}
