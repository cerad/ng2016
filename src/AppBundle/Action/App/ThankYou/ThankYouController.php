<?php

namespace AppBundle\Action\App\ThankYou;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\Request;

class ThankYouController extends AbstractController2
{
    private $view;

    public function __construct(
        ThankYouView  $view
    )
    {
        $this->view  = $view;
    }
    public function __invoke(Request $request)
    {
        return $this->view->render();
    }
}
