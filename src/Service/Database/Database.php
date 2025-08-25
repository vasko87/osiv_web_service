<?php

namespace DbService\Service\Database;

class Database
{
    private $dsnString;
    private $conn;

    public function __construct(array $dbConfig)
    {
        $this->dsnString = sprintf(
            'odbc: DSN=%s;SERVER=%s;UID=%s;PWD=%s;DATABASE=%s',
            $dbConfig['dsn'],
            $dbConfig['server'],
            $dbConfig['user'],
            $dbConfig['pass'],
            $dbConfig['name']
        );
    }

    public function connect(): void
    {
        $this->conn = new \PDO($this->dsnString);
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function execute(string $query): \PDOStatement
    {
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function fetchAll(\PDOStatement $stmt)
    {
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchLazy(\PDOStatement $stmt)
    {
        return $stmt->fetch(\PDO::FETCH_LAZY);
    }

    public function isQuerySelect(string $query): bool
    {
        return preg_match('/^\s*SELECT/i', $query);
    }

    public function fetchTableNames(): array
    {
        $sql = "SELECT TBL FROM sysprogress.systables WHERE TBLTYPE='T'";
        $stmt = $this->execute($sql);

        $res = $this->fetchAll($stmt);

        return is_array($res) ? $res : [];
    }

    public function fetchTableCount(string $table): int
    {
        $sql = "SELECT COUNT(*) FROM pub." . $table;
        $stmt = $this->execute($sql);
        return (int)$stmt->fetchColumn();
    }

    public function fetchTableColumns(string $table): array
        {
            $sql = "SELECT COL, COLTYPE, WIDTH FROM sysprogress.syscolumns WHERE TBL = 'pub." . $table . "'";
            $stmt = $this->execute($sql);
            $res = $this->fetchAll($stmt);

            return is_array($res) ? $res : [];
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

    public function getDbStruct()
    {
        $structure = [];
        foreach ($this->fetchTableNames() as $tableName) {
            $tableName = $tableName['TBL'];
            $structure[$tableName] = ['columns' => []];
            foreach ($this->fetchTableColumns($tableName) as $colData) {
                $structure[$tableName]['columns'][$colData['COL']] = $colData['COLTYPE'] . '(' . $colData['WIDTH'] . ')';
            }
        }

        return $structure;
    }
}
