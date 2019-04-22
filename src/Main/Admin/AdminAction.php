<?php declare(strict_types=1);

namespace Zayso\Main\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\CurrentProject;

class AdminAction implements ActionInterface
{
    use RouterTrait;

    private $project;
    private $template;
    private $switchUserForm;

    public function __construct(
        CurrentProject $project,
        AdminTemplate  $template, // Probably should pull from project
        AdminSwitchUserForm $switchUserForm
    )
    {
        $this->project  = $project;
        $this->template = $template;
        $this->switchUserForm = $switchUserForm;
    }
    public function __invoke(Request $request) : Response
    {
        $this->switchUserForm->handleRequest($request,$this->project);

        if ($this->switchUserForm->isValid()) {
            $formData = $this->switchUserForm->getData();
            $redirect = $formData['username'] ?
                $this->redirectToRoute('app_home',['_switch_user' => $formData['username']]) :
                $this->redirectToRoute('app_admin');
            return $redirect;
        }
        $content = $this->template->render($this->project);
        return new Response($this->project->pageTemplate->render($content));
    }
}
