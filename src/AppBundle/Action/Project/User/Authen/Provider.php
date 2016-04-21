<?php
namespace AppBundle\Action\Project\User\Authen;

use GuzzleHttp\Client        as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Provider
{
    private $params;
    private $guzzleClient;

    public function __construct($params)
    {
        $this->params = $params;
        
        $this->guzzleClient = new GuzzleClient([
            'verify' => false,
        ]);
    }
    public function getAuthorizationUrl()
    {
        $params = $this->params;
        
        $query = [
            'response_type' => 'code',
            'client_id'     => $params['client_id'],
            'scope'         => $params['scope'],
            'redirect_uri'  => $params['callback_uri'],
            'state'         => $params['state'],
        ];
        return $params['authorization_url'] . '?' . http_build_query($query);
    }
    public function getAccessToken($code)
    {
        $params = $this->params;
        
        $query = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $params['client_id'],
            'client_secret' => $params['client_secret'],
            'redirect_uri'  => $params['callback_uri'],
        ];
        $guzzleResponse = $this->guzzleClient->request('POST',$params['access_token_url'], [
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => $query,
        ]);
        return $this->getResponseData($guzzleResponse);
    }
    public function getUserInfoData($accessTokenData)
    {
        $guzzleResponse = $this->guzzleClient->request('GET',$this->params['user_info_url'], [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization'  => 'Bearer ' . $accessTokenData['access_token']
            ],
        ]);
        $data = $this->getResponseData($guzzleResponse); var_dump($data);
        $userInfoData = [];
        foreach($this->params['keys'] as $key) {
            $userInfoData[$key] = isset($data[$key]) ? $data[$key] : null;
        }
        return $userInfoData;
    }
    // Return array from either json or name-value
    private function getResponseData(GuzzleResponse $guzzleResponse)
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