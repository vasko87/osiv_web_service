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
            $date = (new \DateTime($date))->format('d.m.Y');
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
                'JQL' => "project in (PROD, OSIV) and (FixVersion in unreleasedVersions()) and labels = KnownIssue and type = bug",
                'label' => 'Known Issue'
            ]
        ];

        $auth = base64_encode($this->config['user_email'] . ':' . $this->config['api_token']);
        $rows = [];
        $startRow = 9;

        foreach ($iterations as $iteration) {
            $rows[] = [
                'Typ' => $iteration['label'],
                'Internes Ticket' => '',
                'OSD Ticket' => '',
                'Beschreibung' => '',
                'Komponente' => '',
                'Erstellt' => '',
                'Geplant' => ''
            ];

            $url = $this->config['base_url'] . '/rest/api/3/search/jql';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Basic $auth",
                "Accept: application/json",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'jql' => $iteration['JQL'],
                'maxResults' => 1000,
                'fields' => ['*all']
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return new JsonResponse(503, ['error' => "Jira API returned HTTP $httpCode"]);
            }

            $data = json_decode($response, true);
            $issues = $data['issues'] ?? [];

            foreach ($issues as $issue) {
                        $beschreibungField = $iteration['label'] === 'Known Issue'
                            ? ($issue['fields']['customfield_10789'] ?? '')
                            : ($issue['fields']['customfield_10785'] ?? '');

                        $beschreibung = '';

                        if (is_array($beschreibungField)) {
                            $extractTextFromADF = function(array $node) use (&$extractTextFromADF): string {
                                $text = '';
                                if (isset($node['text'])) {
                                    $text .= $node['text'];
                                }
                                if (isset($node['content']) && is_array($node['content'])) {
                                    foreach ($node['content'] as $child) {
                                        $text .= $extractTextFromADF($child);
                                    }
                                }
                                return $text;
                            };

                            $beschreibung = $extractTextFromADF($beschreibungField);
                        } else {
                            $beschreibung = (string)$beschreibungField;
                        }

                        $created = $issue['fields']['created'] ?? '';
                        $createdFormatted = '';
                        if (!empty($created)) {
                            try {
                                $createdFormatted = (new \DateTime($created))->format('d.m.Y');
                            } catch (\Exception $e) {
                                $createdFormatted = $created; // fallback на исходное значение, если невалидная дата
                            }
                        }

                        $rows[] = [
                            'Typ' => $issue['fields']['issuetype']['name'] ?? '',
                            'Internes Ticket' => $issue['key'] ?? '',
                            'OSD Ticket' => $issue['fields']['customfield_10786'] ?? '',
                            'Beschreibung' => $beschreibung,
                            'Komponente' => implode(', ', array_column($issue['fields']['components'] ?? [], 'name')),
                            'Erstellt' => $createdFormatted,
                            'Geplant' => implode(', ', array_column($issue['fields']['fixVersions'] ?? [], 'name'))
                        ];
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

                    $excelLibWrapper->setCellValue('g3', $date);
                    $excelLibWrapper->setCellValue('g2', $fixVersion);

                    $tempFile = tempnam(sys_get_temp_dir(), '_jira_xls');

                    $excelLibWrapper->saveSpreadsheetTo($tempFile);

                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename="' . $this->config['tgt_excel_file'] . ' ' . $fixVersion . '.xlsx"');
                    header('Cache-Control: max-age=0');
                    readfile($tempFile);

                    exit;
                } catch (\Exception $e) {
                    return new JsonResponse(503, ['error' => 'Excel save error: ' . $e->getMessage()]);
                }

                return new JsonResponse(200, ['jira' => count($rows) . " rows added starting from row $startRow"]);
    }
}
