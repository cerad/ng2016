<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Update;


use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\RequestTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\CurrentProject;
use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersonSaver;

final class AdminUpdateAction implements ActionInterface
{
    use RouterTrait;
    use RequestTrait;

    private $project;
    private $updateForm;
    private $template;

    private $regPersonFinder;
    private $regPersonSaver;

    private $requestUrl;
    
    public function __construct(
        CurrentProject  $project,
        RegPersonFinder $regPersonFinder,
        RegPersonSaver  $regPersonSaver,
        AdminUpdateForm $updateForm,
        AdminUpdateTemplate $template
    )
    {
        $this->project    = $project;
        $this->updateForm = $updateForm;
        $this->template   = $template;

        $this->regPersonFinder = $regPersonFinder;
        $this->regPersonSaver  = $regPersonSaver;
    }
    public function __invoke(Request $request, int $regPersonId) : Response
    {
        $regPerson = $this->regPersonFinder->findByRegPersonId($regPersonId);
        
        if ($regPerson === null) {
            return $this->redirectToRoute('reg_person_admin_listing');
        }

        $updateForm = $this->updateForm;
        $updateForm->setRegPerson($regPerson);

        //check for post or not
        $updateForm->handleRequest($request);

        if ($updateForm->isValid()) {

            // Save the data
            $this->regPersonSaver->save($regPerson);

            // respond to save & continue
            if ($request->request->has('save')) {
                $requestUrl = $this->generateUrl(
                    $this->getCurrentRouteName(),
                    ['regPersonId' => $regPersonId]
                );
                return $this->redirect($requestUrl);
            }

            //respond to saveAndReturn
            return $this->redirectToRoute('reg_person_admin_listing'); // TODO Add Fragment
        }
        $content = $this->template->render($regPerson,$updateForm);

        return new Response($this->project->pageTemplate->render($content));
    }
}
