<?php
namespace AppBundle\Action\Project\User\Authen;

use AppBundle\Action\Project\ProjectFactory;
use AppBundle\Action\Project\User\Authen\Provider\AbstractProvider;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;

class ConnectController extends AbstractController
{
    /** @var  ProviderFactory */
    private $providerFactory;

    public function __construct(ProviderFactory $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }
    public function __invoke(Request $request, $providerName)
    {
        $provider = $this->providerFactory->create($providerName);

        //$redirectUrl = $provider->getAuthorizationUrl();

        return $this->redirect($provider->getAuthorizationUrl());
    }
}
