<?php

namespace AppBundle\Action\App\Admin;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\Request;

class AdminController extends AbstractController2
{
    private $switchUserForm;

    public function __construct(
        AdminSwitchUserForm $switchUserForm
    )
    {
        $this->switchUserForm = $switchUserForm;
    }
    public function __invoke(Request $request)
    {
        $this->switchUserForm->handleRequest($request);

        if ($this->switchUserForm->isValid()) {
            $formData = $this->switchUserForm->getData();
            $redirect = $formData['username'] ?
                $this->redirectToRoute('app_home',['_switch_user' => $formData['username']]) :
                $this->redirectToRoute('app_admin');
            return $redirect;
        }
        return null;
    }
}
