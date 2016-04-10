<?php
namespace AppBundle\Action\Project\User\PasswordResetRequest;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetRequestView extends AbstractView
{
    public function __invoke(Request $request)
    {
        return new Response('password reset view');
    }
}