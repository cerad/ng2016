<?php declare(strict_types=1);

namespace Zayso\Main\TextAlerts;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zayso\Common\Contract\ActionInterface;

class TextAlertsAction implements ActionInterface
{
    private $contentTemplate;

    public function __construct(TextAlertsTemplate $contentTemplate)
    {
        $this->contentTemplate = $contentTemplate;
    }
    public function __invoke(Request $request)
    {
        return new Response($this->contentTemplate->render());
    }
}
