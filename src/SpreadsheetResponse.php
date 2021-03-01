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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use URLify;


/**
 * Class SpreadsheetResponse
 *
 * @package CodeInc\SpreadsheetResponse
 * @copyright 2021 Code Inc. <https://www.codeinc.co>
 * @author Joan Fabrégat <joan@codeinc.co>
 */
class SpreadsheetResponse extends StreamedResponse
{
    public const DEFAULT_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * SpreadsheetResponse constructor.
     *
     * @param Spreadsheet $spreadsheet
     * @param string $filename
     * @param int $status
     * @param array $headers
     * @param string $mimeType
     */
    public function __construct(private Spreadsheet $spreadsheet,
                                private string $filename,
                                int $status = self::HTTP_OK,
                                array $headers = [],
                                string $mimeType = self::DEFAULT_MIME_TYPE)
    {
        $writer = new Xlsx($spreadsheet);
        if (!preg_match('/\\.xlsx$/ui', $filename)) {
            $filename .= '.xlsx';
        }
        parent::__construct(
            fn() => $writer->save('php://output'),
            $status,
            array_merge([
                'Content-Type' => $mimeType,
                'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, URLify::slug($filename)),
                'Cache-Control' => 'max-age=0',
            ], $headers)
        );
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }
}