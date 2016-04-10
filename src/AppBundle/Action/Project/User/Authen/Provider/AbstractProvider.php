<?php
namespace AppBundle\Action\Project\User\Authen\Provider;

use GuzzleHttp\Client        as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

use Symfony\Component\Routing\RouterInterface;

abstract class AbstractProvider
{
    
    protected $state = 'google';
    protected $scope = '';

    protected $callbackUri;
    protected $userInfoUrl;
    protected $accessTokenUrl;
    protected $authorizationUrl;

    protected $clientId;
    protected $clientSecret;

    protected $guzzleClient;

    public function __construct(RouterInterface $router, $clientId, $clientSecret)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        
        $this->callbackUri = $router->generate('user_authen_callback',[],RouterInterface::ABSOLUTE_URL);
        
        $this->guzzleClient = new GuzzleClient([
            'verify' => false,
        ]);
    }
    public function getAuthorizationUrl()
    {
        $query = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'scope'         => $this->scope,
            'redirect_uri'  => $this->callbackUri,
            'state'         => $this->state,
        ];
        return $this->authorizationUrl . '?' . http_build_query($query);
    }
    public function getAccessToken($code)
    {
        $query = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->callbackUri,
        ];
        $guzzleResponse = $this->guzzleClient->request('POST',$this->accessTokenUrl, [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => $query,
        ]);
        return $this->getResponseData($guzzleResponse);
    }
    public function getUserInfoData($accessTokenData)
    {
        $guzzleResponse = $this->guzzleClient->request('GET',$this->userInfoUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization'  => 'Bearer ' . $accessTokenData['access_token']
            ],
        ]);
        return $this->getResponseData($guzzleResponse);
    }
    // Return array from either json or name-value
    protected function getResponseData(GuzzleResponse $guzzleResponse)
    {
        $content = (string)$guzzleResponse->getBody();

        if (!$content) return [];

        $json = json_decode($content, true);
        if (JSON_ERROR_NONE === json_last_error()) return $json;

        $data = [];
        parse_str($content, $data);
        return $data;
    }
}