<?php

namespace DbService\Service\Database;

class StmtMock
{
    public function fetchAll(int $fetchStyle = \PDO::FETCH_ASSOC): array
    {
        // Mocked data for testing purposes
        $row = [
            'id' => '%d',
            'res' => 't_%d',
            'name' => 'test%d',
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. T-%d',
            'content_txt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. T-%d ... ' . str_repeat('x', 1024),
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        $cnt = rand(80,150);
        $data = [];
        for ($i = 1; $i <= $cnt; $i++) {
            $data[] = array_map(function($v) use ($i) { return sprintf($v, $i); }, $row);
        }
        return $data;
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
