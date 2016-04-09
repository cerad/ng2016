<?php
namespace AppBundle\Action\Project\User\Authen;

use AppBundle\Action\Project\User\Authen\Provider\AbstractProvider;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;

class ConnectController extends AbstractController
{
    public function __invoke(Request $request, $providerName)
    {
        $serviceId = 'user_authen_provider_' . $providerName;

        /** @noinspection PhpUndefinedFieldInspection */
        if (!$this->container->has($serviceId)) {
            return $this->redirectToRoute('app_welcome');
        }
        /** @var AbstractProvider $provider */
        /** @noinspection PhpUndefinedFieldInspection */
        $provider = $this->container->get($serviceId);
        
        return $this->redirect($provider->getAuthorizationUrl());
    }
}
