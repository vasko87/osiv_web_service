<?php

namespace DbService\Response;

use DbService\Template;

class HtmlTemplateResponse implements Response
{
    private $templateName;
    private $vars;

    public function __construct(string $templateName, array $vars = [])
    {
        $this->templateName = $templateName;
        $this->vars = $vars;
    }

    public function flush(): void
    {
        try {
            $template = new Template($this->templateName);
            echo $template->getContent($this->vars);
        } catch (\Throwable $t) {
            (new HtmlResponse(500, $t->getMessage()))->flush();
        }
    }
}
