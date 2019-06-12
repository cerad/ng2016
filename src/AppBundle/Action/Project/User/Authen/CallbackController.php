<?php
namespace AppBundle\Action\Project\User\Authen;

use AppBundle\Action\AbstractController;
use AppBundle\Action\Project\User\ProjectUserProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CallbackController extends AbstractController
{
    private $providerFactory;

    private $userProvider;

    public function __construct(ProviderFactory $providerFactory, ProjectUserProvider $userProvider)
    {
        $this->userProvider    = $userProvider;
        $this->providerFactory = $providerFactory;
    }
    public function __invoke(Request $request)
    {
        if (!$request->query->has('code')) {
            return $this->redirectToRoute('app_welcome');
        }
        
        $code         = $request->query->get('code');
        $providerName = $request->query->get('state');

        $provider = $this->providerFactory->create($providerName);
        
        $accessTokenData = $provider->getAccessToken($code);

        $userData = $provider->getUserInfoData($accessTokenData);

        $email = $userData['email'];

        try {
            $user = $this->userProvider->loadUserByUsername($email);
            $this->loginUser($request,$user);
            return $this->redirectToRoute('app_home');
        }
        catch (UsernameNotFoundException $e) {
        }
        return $this->redirectToRoute('app_welcome');

        //var_dump($userData);
        //return $this->redirect($provider->getAuthorizationUrl());
        
        //return new Response('callback');
    }
    private function loginUser(Request $request, UserInterface $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get("security.token_storage")->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $event);
    }
    /*
     * http://local.ng2019.zayso.org/user/authen/callback?
     * state=google&
     * code=4/E3lM9yHYStqgG-ejUqvFxacpTPapM2vq3zYnU8Vo4ys&authuser=0&
     * session_state=4f155b1fa59616c0d0a22bb58faa6236446cd5a3..9c1d&
     * prompt=consent#
     */
    /*
     * array(4) { [
     * "access_token"]=> string(72) "ya29..vwJJ3D9xag1bxsKT8aP6H2b81JMVkyX-yqyeZ3Tqa96Mf2hrYcRiuP9-jvy8fMCedA" [
     * "token_type"]=> string(6) "Bearer" [
     * "expires_in"]=> int(3599)
     * ["id_token"]=> string(885) very long string
    /*
     * array(10) { [
     * "id"]=> string(21) "113055156735633728525" [
     * "email"]=> string(18) "web.ng2019@gmail.com" [
     * "verified_email"]=> bool(true) [
     * "name"]=> string(11) "Art Hundiak" [
     * "given_name"]=> string(3) "Art" [
     * "family_name"]=> string(7) "Hundiak" [
     * "link"]=> string(45) "https://plus.google.com/113055156735633728525" [
     * "picture"]=> string(92) "https://lh5.googleusercontent.com/-Se381VF6fkk/AAAAAAAAAAI/AAAAAAAAADM/dfDv8FJXKCw/photo.jpg" [
     * "gender"]=> string(4) "male" [
     * "locale"]=> string(2) "en" } 
     */
}
