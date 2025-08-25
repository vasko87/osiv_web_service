<?php

namespace DbService;

class Request
{
    public $method;

    private $data;

    public function __construct()
    {
        $this->data = $_REQUEST;
        $_REQUEST = $_GET = $_POST = null;

        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    public function getString(string $key): ?string
    {
        $val = $this->data[$key] ?? null;
        return (string)$val;
    }

    public function getBool(string $key): ?string
    {
        $val = $this->data[$key] ?? null;
        return !empty($val);
    }

    public function getArray(string $key): ?array
    {
        $val = $this->data[$key] ?? null;

        return is_array($val) ? $val : null;
    }

    private function sanitizeWord($val): string
    {
        if (!is_string($val)) {
            return '';
        }
        return preg_replace('/[^A-Za-z0-9_-]+/', '', $val);
    }

    public function getControllerName(): ?string
    {
        $controller = $this->getString('c');
        if (empty($controller)) {
            return null;
        }
        return strtolower($this->sanitizeWord($controller));
    }

    public function getActionName(): ?string
    {
        $action = $this->getString('a');
        if (empty($action)) {
            return null;
        }
        return strtolower($this->sanitizeWord($action));
    }

    public function getActionFromPost(): ?string
    {
        $action = $this->getArray('action');
        if (empty($action)) {
            return null;
        }

        return strtolower($this->sanitizeWord(key($action)));
    }
}
