<?php declare(strict_types=1);

namespace Zayso\Main\Home;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Project\CurrentProject;
use Zayso\Reg\Person\RegPersonFinder;

class HomeAction implements ActionInterface
{
    use AuthenticationTrait;

    private $currentProject;
    private $homeTemplate;
    private $regPersonFinder;

    public function __construct(
        CurrentProject  $currentProject,
        HomeTemplate    $homeTemplate,
        RegPersonFinder $regPersonFinder
    ){
        $this->currentProject  = $currentProject;
        $this->homeTemplate    = $homeTemplate;
        $this->regPersonFinder = $regPersonFinder;
    }
    public function __invoke(Request $request)
    {
        // Should check authorization?
        $user = $this->getUser();

        $regPerson = $this->regPersonFinder->findByProjectPerson($this->currentProject->projectId, $user->personId);

        return new Response($this->homeTemplate->render($user, $this->currentProject, $regPerson));
    }
}
