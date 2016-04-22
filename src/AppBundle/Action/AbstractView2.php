<?php
namespace AppBundle\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractView2 implements ContainerAwareInterface
{
    use AbstractActionTrait;
    
    /** @var  ContainerInterface */
    protected $container;

    /** =============================================
     * In theory these template classes should only be in AbstractView
     * Resist the temptation to render pages in the controller
     *
     * @return BaseTemplate
     */
    protected function getBaseTemplate()
    {
        return $this->container->get('app_base_template');
    }
    protected function renderBaseTemplate($content)
    {
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }

    /** 
     * @param $content string
     * @param $status  integer
     * @return Response
     */
    protected function newResponse($content = null, $status = 200)
    {
        return new Response($content,$status);
    }
}