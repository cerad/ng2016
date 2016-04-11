<?php
namespace AppBundle\Action\Project\User\Authen;

use Symfony\Component\Routing\RouterInterface;

class ProviderFactory
{
    /** @var  RouterInterface */
    private $router;
    
    private $providers;
    
    public function __construct(RouterInterface $router,$providers)
    {
        $this->router    = $router;
        $this->providers = $providers;
    }
    /** 
     * @param $providerName string
     * @return Provider
     */
    public function create($providerName)
    {
        $params = isset($this->providers[$providerName]) ? $this->providers[$providerName] : null;
        if (!$params) {
            throw new \InvalidArgumentException;
        }
        $params['callback_uri'] = $this->router->generate('user_authen_callback',[],RouterInterface::ABSOLUTE_URL);
        
        return new Provider($params);
    }
}