<?php
namespace AppBundle\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractForm implements ContainerAwareInterface
{
    use AbstractActionTrait;
    
    /** @var  ContainerInterface */
    protected $container;
    
    protected $isPost = false;
    
    protected $submit;
    
    protected $formData       = [];
    protected $formDataErrors = [];
    
    public function setData($formData)
    {
        $this->formData = array_replace_recursive($this->formData, $formData);
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
    public function getSubmit() 
    {
        return $this->submit;
    }
    /** 
     * @param string $id
     * @return DataTransformerInterface
     */
    protected function getTransformer($id)
    {
        return $this->container->get($id);
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