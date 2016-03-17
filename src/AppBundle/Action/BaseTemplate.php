<?php
namespace AppBundle\Action;

class BaseTemplate
{
    protected $title = 'NG2016';

    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
    protected function renderStylesheets()
    {
        return null;
    }
    protected function renderJavascripts()
    {
        return null;
    }
    public function render($content = null)
    {
        return <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>{$this->escape($this->title)}</title>
        {$this->renderStylesheets()}
        <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        {$content}
        {$this->renderJavascripts()}
    </body>
</html>
EOT;
    }
}