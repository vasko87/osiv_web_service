<?php

namespace DbService\Response;

class HtmlResponse implements Response
{
    private $code;
    private $content;
    private $headers;

    public function __construct(int $code, string $content = null, array $headers = [])
    {
        $this->code = $code;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function flush(): void
    {
        http_response_code($this->code);
        foreach ($this->headers as $header) {
            header($header);
        }

        header('Content-Type: text/html; charset=utf-8');

        echo $this->content;
    }
}
