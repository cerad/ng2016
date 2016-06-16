<?php
namespace AppBundle\Action\Game\Import;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\Request;

class GameImportController extends AbstractController2
{
    private $form;
    
    public function __construct(GameImportForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
        $formData = [
            'op'   => 'verify',
            'file' => null,
        ];
        $this->form->setData($formData);
        $this->form->handleRequest($request);
        if ($this->form->isValid()) {
            $formData = $this->form->getData();
            dump($formData);
        }
        return null;
    }
}