<?php

namespace DbService;

class Template
{
    private $templateName;
    private $templatePath;

    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;
        $this->templatePath = $this->getPath();

        if (!is_file($this->templatePath)) {
            throw new \InvalidArgumentException('Cannot find template: ' . $this->templateName);
        }
    }

    public function getContent(array $vars): string
    {
        extract($vars);
        ob_start();
        require $this->templatePath;

        return ob_get_clean();
    }

    private function getPath(): string
    {
        return PATH_TEMPLATE . '/' . preg_replace('~[^A-Za-z0-9_/-]+~', '', $this->templateName) . '.php';
    }
}
