<?php

declare(strict_types=1);

namespace CodeInc\SpreadsheetResponse\Tests;

use CodeInc\SpreadsheetResponse\SpreadsheetResponse;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpreadsheetResponseTest extends TestCase
{
    private function createSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello');
        $sheet->setCellValue('B1', 'World');
        return $spreadsheet;
    }

    public function testDefaultXlsxResponse(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'test.xlsx');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('test.xlsx', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('max-age=0', $response->headers->get('Cache-Control'));
    }

    public function testCustomStatusCode(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'test.xlsx', 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testInlineDisposition(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse(
            $spreadsheet,
            'test.xlsx',
            200,
            null,
            'inline'
        );

        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function testExtraHeaders(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse(
            $spreadsheet,
            'test.xlsx',
            200,
            null,
            'attachment',
            ['X-Custom-Header' => 'custom-value']
        );

        $this->assertSame('custom-value', $response->headers->get('X-Custom-Header'));
    }

    #[DataProvider('writerProvider')]
    public function testDifferentWriters(string $writerClass, string $expectedMimeType, string $expectedExtension): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $writer = new $writerClass($spreadsheet);
        $response = new SpreadsheetResponse($spreadsheet, 'test', 200, $writer);

        $this->assertSame($expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertStringContainsString("test.$expectedExtension", $response->headers->get('Content-Disposition'));
    }

    public static function writerProvider(): array
    {
        return [
            'xlsx' => [Writer\Xlsx::class, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
            'xls' => [Writer\Xls::class, 'application/vnd.ms-excel', 'xls'],
            'csv' => [Writer\Csv::class, 'text/csv', 'csv'],
            'ods' => [Writer\Ods::class, 'application/vnd.oasis.opendocument.spreadsheet', 'ods'],
            'html' => [Writer\Html::class, 'text/html', 'html'],
        ];
    }

    public function testFilenameWithoutExtensionGetsExtensionAdded(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'myfile');

        $this->assertStringContainsString('myfile.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function testFilenameWithCorrectExtensionNotDuplicated(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'myfile.xlsx');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('myfile.xlsx', $contentDisposition);
        $this->assertStringNotContainsString('myfile.xlsx.xlsx', $contentDisposition);
    }

    public function testFilenameSanitization(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'file:with/special\\chars.xlsx');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('file-with-special-chars.xlsx', $contentDisposition);
    }

    public function testFallbackFilenameSanitization(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'fichier été.xlsx');

        $contentDisposition = $response->headers->get('Content-Disposition');
        // The fallback filename should only contain ASCII characters
        $this->assertMatchesRegularExpression('/filename=[a-z0-9\\-\\.]+/i', $contentDisposition);
    }

    public function testUnsupportedWriterThrowsException(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $unsupportedWriter = $this->createMock(Writer\IWriter::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not supported/');

        new SpreadsheetResponse($spreadsheet, 'test.xlsx', 200, $unsupportedWriter);
    }

    public function testResponseIsStreamable(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $response = new SpreadsheetResponse($spreadsheet, 'test.xlsx');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // XLSX files start with PK (ZIP signature)
        $this->assertStringStartsWith('PK', $content);
    }

    public function testCsvResponseContent(): void
    {
        $spreadsheet = $this->createSpreadsheet();
        $writer = new Writer\Csv($spreadsheet);
        $response = new SpreadsheetResponse($spreadsheet, 'test.csv', 200, $writer);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Hello', $content);
        $this->assertStringContainsString('World', $content);
    }

    public function testWritersDefaultsConstant(): void
    {
        $this->assertArrayHasKey(Writer\Xlsx::class, SpreadsheetResponse::WRITERS_DEFAULTS);
        $this->assertArrayHasKey(Writer\Xls::class, SpreadsheetResponse::WRITERS_DEFAULTS);
        $this->assertArrayHasKey(Writer\Html::class, SpreadsheetResponse::WRITERS_DEFAULTS);
        $this->assertArrayHasKey(Writer\Pdf::class, SpreadsheetResponse::WRITERS_DEFAULTS);
        $this->assertArrayHasKey(Writer\Ods::class, SpreadsheetResponse::WRITERS_DEFAULTS);
        $this->assertArrayHasKey(Writer\Csv::class, SpreadsheetResponse::WRITERS_DEFAULTS);

        foreach (SpreadsheetResponse::WRITERS_DEFAULTS as $writerClass => $defaults) {
            $this->assertArrayHasKey('mimeType', $defaults);
            $this->assertArrayHasKey('extension', $defaults);
            $this->assertNotEmpty($defaults['mimeType']);
            $this->assertNotEmpty($defaults['extension']);
        }
    }
}
