<?php

namespace DbService\Service\Database;

class DatabaseMock
{
    public function __construct(array $dbConfig)
    {
        // not implemented
    }

    public function connect(): void
    {
        // not implemented
    }

    public function execute(string $query): StmtMock
    {
        return new StmtMock();
    }

    public function fetchAll(StmtMock $stmt)
    {
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchLazy(StmtMock $stmt)
    {
        // not implemented
    }

    public function isQuerySelect(string $query): bool
    {
        return preg_match('/^\s*SELECT/i', $query);
    }

    public function fetchTableNames(): array
    {
        return [
            ['TBL' => 'table1'],
            ['TBL' => 'table2'],
            ['TBL' => 'table3'],
            ['TBL' => 'table4'],
            ['TBL' => 'table5'],
        ];
    }

    public function fetchTableCount(string $table): int
    {
        return rand(1, 10000);
    }

    public function fetchTableReport(): array
    {
        $report = [];
        foreach ($this->fetchTableNames() as $tableName) {
            $tableName = $tableName['TBL'];
            $report[$tableName] = $this->fetchTableCount($tableName);
        }

        return $report;
    }

    public function fixEncoding($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'fixEncoding'], $input);
        } elseif (is_string($input)) {
            return iconv('Windows-1252', 'UTF-8//IGNORE', $input);
        }
        return $input;
    }

    public function getDbStruct() {
        $x = rand(1,100);
        return [
            'table1' => ['columns' => [
                'id' => 'int',
                'name' => 'varchar(255)',
                'created_at' => 'datetime',
            ]],
            'table' . $x => ['columns' => [
                'id' => 'int',
                'name' => 'varchar(255)',
                'created_at' => 'datetime',
            ]],
        ];
    }
}
