<?php
namespace AppBundle\Action\Project\User\Authen\Provider;

class GoogleProvider extends AbstractProvider
{
    protected $state = 'google';
    protected $scope = 'openid profile email';

    protected $userInfoUrl      = 'https://www.googleapis.com/oauth2/v2/userinfo';
    protected $accessTokenUrl   = 'https://accounts.google.com/o/oauth2/token';
    protected $authorizationUrl = 'https://accounts.google.com/o/oauth2/auth';
}