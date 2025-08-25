<?php

namespace DbService\Response;

class JsonResponsePretty extends JsonResponse
{
    protected function printData(): void
    {
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
