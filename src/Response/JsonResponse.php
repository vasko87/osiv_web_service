<?php

namespace DbService\Response;

class JsonResponse implements Response
{
    private $code;
    private $headers;
    protected $data;

    public function __construct(int $code, array $data = [], array $headers = [])
    {
        $this->code = $code;
        $this->data = $data;
        $this->headers = $headers;
    }

    public function flush(): void
    {
        http_response_code($this->code);
        foreach ($this->headers as $header) {
            header($header);
        }

        header('Content-Type: application/json; charset=utf-8');

        if (!empty($this->data)) {
            $this->printData();
        }
    }

    protected function printData(): void
    {
        echo json_encode($this->data);
    }
}
