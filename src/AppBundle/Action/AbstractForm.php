<?php
namespace AppBundle\Action;

use AppBundle\Common\RenderEscapeTrait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractForm
{
    use RenderEscapeTrait;
    
    /** @var  RouterInterface */
    private $router;

    protected $isPost = false;
    
    protected $formData;
    protected $formDataErrors = [];

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }
    public function setData($formData)
    {
        $this->formData = $formData;
    }
    public function getData()
    {
        return $this->formData;
    }
    public function isValid()
    {
        if (!$this->isPost) return false;
        if (count($this->formDataErrors)) return false;
        return true;
    }
    abstract function handleRequest(Request $request);
    
    abstract public function render();

    protected function renderFormErrors()
    {
        $errors = $this->formDataErrors;

        if (count($errors) === 0) return null;

        $html = '<div class="errors" style="color: #0000FF">' . "\n";
        foreach($errors as $name => $items) {
            foreach($items as $item) {
                $html .= <<<EOD
<div>{$item['msg']}</div>
EOD;
            }}
        $html .= '</div>' . "\n";
        return $html;
    }
}