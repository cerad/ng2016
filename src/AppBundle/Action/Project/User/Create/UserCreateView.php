<?php
namespace AppBundle\Action\Project\User\Create;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserCreateView extends AbstractView
{
    /** @var  UserCreateForm */
    private $userCreateForm ;

    public function __invoke(Request $request)
    {
        $this->userCreateForm = $request->attributes->get('userCreateForm');

        return new Response($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<h3>Create Zayso User</h3>
{$this->userCreateForm->render()}
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
}