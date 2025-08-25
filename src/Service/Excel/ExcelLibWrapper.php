<?php

namespace DbService\Service\Excel;

require __DIR__ . '/../../../vendor/PhpSpreadsheet-1.23.0/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExcelLibWrapper
{
    private $spreadsheet;
    private $worksheet;

    public function loadSpreadsheet(string $filePath)
    {
        $this->spreadsheet = IOFactory::load($filePath);
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public function getCellValue(string $cell)
    {
        return $this->worksheet->getCell($cell)->getValue();
    }

    public function setCellValueByColumnAndRow($col, $currentRow, $value)
    {
        $cell = $this->worksheet->getCellByColumnAndRow($col, $currentRow);
        $cell->setValueExplicit($value, DataType::TYPE_STRING);
    }

    public function setCellValue(string $cell, $value)
    {
        $this->worksheet->getCell($cell)->setValueExplicit($value, DataType::TYPE_STRING);
    }

    public function saveSpreadsheetTo(string $filePath)
    {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $writer->save($filePath);
    }
}
