<?php
namespace AppBundle\Action\Project\User\Create;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserCreateView extends AbstractView
{
    public function __invoke(Request $request)
    {
        return new Response('Create User');
    }
}