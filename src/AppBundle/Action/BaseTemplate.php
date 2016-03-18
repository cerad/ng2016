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
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->escape($this->title)}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="/css/normalize.css" media="all" />
    <link rel="stylesheet" type="text/css" href="/css/zayso.css" media="all" />
  </head>
  <body>
    {$content}
    {$this->renderJavascripts()}
  </body>
</html>
EOT;
    }
}