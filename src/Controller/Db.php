<?php

namespace DbService\Controller;

use DbService\Request;
use DbService\Response\HtmlTemplateResponse;
use DbService\Response\JsonResponsePretty;
use DbService\Response\RedirectResponse;
use DbService\Response\Response;
use DbService\Response\JsonResponse;
use DbService\Service\Database\Database;
use DbService\Service\Database\DatabaseMock;

class Db extends Base
{
    private $dbConfig;

    public function __construct(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    public function actionIndex(Request $request): HtmlTemplateResponse
    {
        session_start();
        $currentComparisonState = $_SESSION['db_comparison'] ?? null;

        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $debug = true;
        } else {
            $debug = false;
        }

        return new HtmlTemplateResponse(
            'db_home',
            [
                'dbOpts' => $this->dbConfig,
                'form' => $request->getString('form'),
                'currentComparisonState' => $currentComparisonState,
                'debug' => $debug,
            ]
        );
    }

    public function actionQuery(Request $request): Response
    {
        $dbVal = $request->getString('db');
        $dbConfig = $this->dbConfig[$dbVal] ?? null;
        if (empty($dbConfig)) {
            return new JsonResponse(400, ['error' => "'db' query parameter is invalid"]);
        }

        $shouldSortFields = $request->getBool('sortFields');
        $query = $request->getString('q');

        try {
            $database = $this->getDatabase($dbConfig, $request->getBool('debug'));
            $database->connect();

            $isSelect = $database->isQuerySelect($query);

            $stmt = $database->execute($query);

            if ($isSelect) {
                $data = $database->fetchAll($stmt);
                $data = $database->fixEncoding($data);

                if ($shouldSortFields) {
                    foreach ($data as &$row) {
                        if (is_array($row)) {
                            ksort($row);
                        }
                    }
                    unset($row);
                }

                if (empty($data)) {
                    $stmt->execute();
                    $data = [];
                    while ($row = $database->fetchLazy($stmt)) {
                        $row = json_decode(json_encode($row), true);
                        $data[] = $database->fixEncoding($row);
                    }
                }

                if (!is_array($data)) {
                    $data = [];
                }

                return new JsonResponsePretty(200, $data);
            } else {
                $rowCount = $stmt->rowCount();

                return new JsonResponsePretty(
                    200,
                    [
                        'status' => 'OK',
                        'rows_affected' => $rowCount
                    ]
                );
            }
        } catch (\Throwable $t) {
            return new JsonResponsePretty(503, ['error' => 'Error: ' . $t->getMessage()]);
        }
    }

    public function actionComparison(Request $request): Response
    {
        $action = $request->getString('action');
        if (empty($action)) {
            return new JsonResponse(400, ['error' => "'action' parameter is invalid"]);
        }

        session_start();

        $dbVal = $request->getString('db');
        $dbConfig = $this->dbConfig[$dbVal] ?? null;
        if (empty($dbConfig)) {
            return new JsonResponse(400, ['error' => "'db' query parameter is invalid"]);
        }
        $database = $this->getDatabase($dbConfig, $request->getBool('debug'));

        switch ($action) {
            case 'activate':
                $database->connect();
                $_SESSION['db_comparison'] = [
                    'state' => 'active',
                    'initial' => $database->fetchTableReport(),
                ];
                break;
            case 'done':
                $database->connect();
                $_SESSION['db_comparison']['state'] = 'done';
                $_SESSION['db_comparison']['diff'] = $this->getDiff($_SESSION['db_comparison']['initial'], $database->fetchTableReport());
                unset($_SESSION['db_comparison']['initial']);
                break;
            case 'stop':
            default:
                $_SESSION['db_comparison'] = null;
                break;
        }

        return new JsonResponse(200, [
            'status' => 'success',
            'comparisonState' => $_SESSION['db_comparison']
        ]);
    }

    public function actionGetDbStruct(Request $request): Response
    {
        $dbVal = $request->getString('db');
        $dbConfig = $this->dbConfig[$dbVal] ?? null;
        if (empty($dbConfig)) {
            return new JsonResponse(400, ['error' => "'db' query parameter is invalid"]);
        }
        $database = $this->getDatabase($dbConfig, $request->getBool('debug'));
        $database->connect();

        return new JsonResponsePretty(200, ['struct' => $database->getDbStruct()]);
    }

    private function getDiff(array $before, array $after): array
    {
        $diff = [];
        $sameRecords = [];
        foreach ($before as $name => $count) {
            if (!isset($after[$name])) {
                $diff[$name] = [
                    'type' => 'removed',
                    'before' => $count,
                    'after' => null,
                ];
            } else {
                $sameRecords[$name] = false;
            }
        }
        foreach ($after as $name => $count) {
            if (!isset($before[$name])) {
                $diff[$name] = [
                    'type' => 'new',
                    'before' => null,
                    'after' => $count,
                ];
            } else {
                $sameRecords[$name] = false;
            }
        }
        foreach ($sameRecords as $name => $_) {
            $a = $after[$name];
            $b = $before[$name];
            $diff[$name] = [
                'type' => $a === $b ? 'same' : ($a > $b ? 'more' : 'less'),
                'before' => $b,
                'after' => $a,
            ];
        }

        ksort($diff);
        uasort($diff, function ($a, $b) {
            $map = [
                'new' => 0,
                'removed' => 1,
                'more' => 2,
                'less' => 2,
                'same' => 3,
            ];
            $xa = $map[$a['type']];
            $xb = $map[$b['type']];

            if ($xb > $xa) {
                return -1;
            } elseif ($xb < $xa) {
                return 1;
            }
        });

        return $diff;
    }

    private function getDatabase(array $dbConfig, bool $debugMode)
    {
         return $debugMode ? new DatabaseMock($dbConfig) : new Database($dbConfig);
    }
}
