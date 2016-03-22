<?php
namespace AppBundle\Action;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractController extends Controller
{
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
    /**
     * @return BaseTemplate
     */
    protected function getBaseTemplate()
    {
        return $this->get('app_base_template');
    }
    protected function getCurrentProject()
    {
        return $this->getParameter('app_project');
    }
}