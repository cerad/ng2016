<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

use Zayso\Common\Template\PageTemplate;

// Eventually want to pull the page template from the project
trait RenderTrait
{
    /** @var PageTemplate */
    protected $pageTemplate;

    /** @required */
    public function setPageTemplate(PageTemplate $pageTemplate)
    {
        $this->pageTemplate = $pageTemplate;
    }
    protected function renderPageTemplate(string $content) : string
    {
        return $this->pageTemplate->render($content);
    }
}