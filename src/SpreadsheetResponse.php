<?php
/******************************************************************************
 * Copyright (c) 2021 Code Inc. - All Rights Reserved                         *
 * Unauthorized copying of this file, via any medium is strictly prohibited   *
 * Proprietary and confidential                                               *
 * Written by Joan Fabrégat <joan@codeinc.co>, 03/2021                        *
 * Visit https://www.codeinc.co for more information                          *
 ******************************************************************************/

declare(strict_types=1);

namespace CodeInc\SpreadsheetResponse;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use InvalidArgumentException;

/**
 * Class SpreadsheetResponse
 *
 * @package CodeInc\SpreadsheetResponse
 * @copyright 2021 Code Inc. <https://www.codeinc.co>
 * @author Joan Fabrégat <joan@codeinc.co>
 */
class SpreadsheetResponse extends StreamedResponse
{
    public const WRITERS_DEFAULTS = [
        Writer\Xlsx::class => [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension'=> 'xlsx',
        ],
        Writer\Xls::class => [
            'mimeType' => 'application/vnd.ms-excel',
            'extension'=> 'xls',
        ],
        Writer\Html::class => [
            'mimeType' => 'text/html',
            'extension'=> 'html',
        ],
        Writer\Pdf::class => [
            'mimeType' => 'application/pdf',
            'extension'=> 'pdf',
        ],
        Writer\Ods::class => [
            'mimeType' => 'application/vnd.oasis.opendocument.spreadsheet',
            'extension'=> 'ods',
        ],
        Writer\Csv::class => [
            'mimeType' => 'text/csv',
            'extension'=> 'csv',
        ],
    ];

    /**
     * SpreadsheetResponse constructor.
     *
     * @param Spreadsheet $spreadsheet The spreadsheet to send
     * @param string $filename The filename of the spreadsheet
     * @param int $status The response status code
     * @param Writer\IWriter|null $writer The writer to use to save the spreadsheet
     * @param string $disposition The disposition of the response
     * @param array $extraHeaders Extra headers to add to the response
     */
    public function __construct(Spreadsheet $spreadsheet,
                                string $filename,
                                int $status = self::HTTP_OK,
                                ?Writer\IWriter $writer = null,
                                string $disposition = 'attachment',
                                array $extraHeaders = [])
    {
        $writer ??= new Writer\Xlsx($spreadsheet);
        $defaults = $this->getWriterDefaults($writer);
        parent::__construct(
            fn() => $writer->save('php://output'),
            $status,
            array_merge([
                'Content-Type' => $defaults['mimeType'],
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    $disposition,
                    $this->enforceExtension($this->sanitizeFilename($filename), $defaults['extension']),
                    $this->enforceExtension($this->sanitizeFallbackFilename($filename), $defaults['extension']),
                ),
                'Cache-Control' => 'max-age=0',
            ], $extraHeaders)
        );
    }

    /**
     * Sanitizes the filename.
     * 
     * @param string $filename
     * @return string
     */
    #[Pure]
    private function sanitizeFilename(string $filename): string
    {
        return strtr($filename, ':\\/', '---');
    }

    /**
     * Sanitizes the fallback filename.
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFallbackFilename(string $filename): string
    {
        return preg_replace('/[^a-z0-9\\-.]+/ui', '-', $filename);
    }

    /**
     * Enforces the extension of the filename.
     *
     * @param string $filename
     * @param string $extension
     * @return string
     */
    private function enforceExtension(string $filename, string $extension): string
    {
        if (!str_ends_with($filename, $extension)) {
            $filename .= ".$extension";
        }
        return $filename;
    }

    /**
     * Returns the default mime type and extension for the writer.
     *
     * @param Writer\IWriter $writer
     * @return string[]
     */
    #[ArrayShape(['mimeType' => 'string', 'extension' => 'string'])]
    private function getWriterDefaults(Writer\IWriter $writer): array
    {
        $writerClass = get_class($writer);
        if (!array_key_exists($writerClass, self::WRITERS_DEFAULTS)) {
            throw new InvalidArgumentException(sprintf("The writer %s is not supported.", $writerClass));
        }
        return self::WRITERS_DEFAULTS[$writerClass];
    }
}