<?php

namespace DbService\Response;

class RedirectResponse implements Response
{
    private $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    public function flush(): void
    {
        header('Location: ' . $this->location);
    }
}
