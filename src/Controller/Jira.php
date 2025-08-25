<?php

namespace DbService\Controller;

use DbService\Request;
use DbService\Response\HtmlTemplateResponse;
use DbService\Response\JsonResponse;
use DbService\Service\Excel\ExcelLibWrapper;

class Jira extends Base
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function actionIndex(Request $request): HtmlTemplateResponse
    {
        return new HtmlTemplateResponse('jira');
    }

    public function actionFetch(Request $request): JsonResponse
    {
        $date = $request->getString('date');
        $fixVersion = $request->getString('version');

        if (empty($fixVersion) || empty($date)) {
            return new JsonResponse(400, ['error' => "'version' and 'date' query parameters are required"]);
        }

        try {
            $date = new \DateTime($date);
            $date = $date->format('d/m/Y');
        } catch (\Exception $e) {
            return new JsonResponse(400, ['error' => 'Invalid date format']);
        }

        $excelLibWrapper = new ExcelLibWrapper();
        $excelLibWrapper->loadSpreadsheet($this->config['src_excel_file']);

        $iterations = [
            [
                'JQL' => "project in (OSIV, PROD) and type not in (bug, task) and fixVersion in ($fixVersion) and \"ReleaseNotesRelevant[Dropdown]\" = Yes and (resolution = empty or resolution not in (\"Cannot Reproduce\", Duplicate, Declined, \"Won't Do\")) order by type ASC",
                'label' => 'Verbesserung'
            ],
            [
                'JQL' => "project in (OSIV, PROD) and type in (bug, task) and fixVersion in ($fixVersion) and \"ReleaseNotesRelevant[Dropdown]\" = Yes and (resolution = empty or resolution not in (\"Cannot Reproduce\", Duplicate, Declined, \"Won't Do\")) order by type ASC",
                'label' => 'Fehlerbehebung'
            ],
            [
                'JQL' => "project in (PROD, OSIV) and (FixVersion in unreleasedVersions() or FixVersion = empty) and labels = KnownIssue and type = bug",
                'label' => 'Known Issue'
            ]
        ];

        $rows = [];
        $startRow = 9;

        $auth = base64_encode($this->config['user_email'] . ':' . $this->config['api_token']);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Basic $auth\r\n" .
                            "Accept: application/json\r\n"
            ]
        ]);

        foreach ($iterations as $iteration) {
            $rows[] = ['Typ' => $iteration['label']];

            $url = $this->config['base_url'] . '/rest/api/2/search?jql=' . urlencode($iteration['JQL']) . '&maxResults=100';

            try {
                $response = file_get_contents($url, false, $context);
                if ($response === false) throw new Exception('Error fetching data from Jira');

                $data = json_decode($response, true);
                $issues = $data['issues'] ?? [];

                foreach ($issues as $issue) {
                    $rows[] = [
                        'Typ' => $issue['fields']['issuetype']['name'] ?? '',
                        'Internes Ticket' => $issue['key'] ?? '',
                        'OSD Ticket' => $issue['fields']['customfield_10786'] ?? '',
                        'Beschreibung' => $issue['fields']['summary'] ?? '',
                        'Komponente' => implode(', ', array_column($issue['fields']['components'] ?? [], 'name')),
                        'Geplant' => implode(', ', array_column($issue['fields']['fixVersions'] ?? [], 'name'))
                    ];
                }
            } catch (\Exception $e) {
                return new JsonResponse(503, ['error' => 'JQL Error: ' . $e->getMessage()]);
            }
        }

        try {
            $currentRow = $startRow;
            foreach ($rows as $rowData) {
                $col = 1;
                foreach ($rowData as $value) {
                    $excelLibWrapper->setCellValueByColumnAndRowWithBorder($col, $currentRow, $value);
                    $col++;
                }
                $currentRow++;
            }

            $excelLibWrapper->setCellValue('f3', $date);
            $excelLibWrapper->setCellValue('f2', $fixVersion);

            $tempFile = tempnam(sys_get_temp_dir(), '_jira_xls');

            $excelLibWrapper->saveSpreadsheetTo($tempFile);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $this->config['tgt_excel_file'] . '"');
            header('Cache-Control: max-age=0');
            readfile($tempFile);

            exit;
        } catch (\Exception $e) {
            return new JsonResponse(503, ['error' => 'Excel save error: ' . $e->getMessage()]);
        }

        return new JsonResponse(200, ['jira' => count($rows) . " rows added starting from row $startRow"]);
    }
}
