<?php

namespace DbService\Service\Database;

class StmtMock
{
    public function fetchAll(int $fetchStyle = \PDO::FETCH_ASSOC): array
    {
        // Mocked data for testing purposes
        return [
            ['res' => 'test1'],
            ['res' => 'test2'],
        ];
    }

    public function rowCount(): int
    {
        // Mocked data for testing purposes
        return 42;
    }

    public function fetchColumn()
    {
        // Mocked data for testing purposes
        return 8;
    }
}
