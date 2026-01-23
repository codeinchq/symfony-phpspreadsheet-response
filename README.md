# Symfony PhpSpreadsheet HTTP response

[![Tests](https://github.com/codeinchq/symfony-phpspreadsheet-response/actions/workflows/tests.yml/badge.svg)](https://github.com/codeinchq/symfony-phpspreadsheet-response/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/codeinc/symfony-phpspreadsheet-response)](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response)
[![Packagist Downloads](https://img.shields.io/packagist/dt/codeinc/symfony-phpspreadsheet-response)](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response)
[![Packagist License](https://img.shields.io/packagist/l/codeinc/symfony-phpspreadsheet-response)](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response)
[![PHP Version](https://img.shields.io/packagist/php-v/codeinc/symfony-phpspreadsheet-response)](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response)

A simple Symfony HTTP response for [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) written in PHP 8 intended to be used with Symfony's [HTTPFoundation component](https://symfony.com/components/HttpFoundation).

## Features

- Supports multiple formats: **XLSX**, **XLS**, **CSV**, **ODS**, **HTML**, and **PDF**
- Automatic MIME type and extension handling
- UTF-8 filename support
- Streamed response for memory efficiency
- Compatible with PHP 8.1+, Symfony 6/7, and PHPSpreadsheet 1.29+

## Usage

### Basic usage (XLSX)

```php
<?php
use CodeInc\SpreadsheetResponse\SpreadsheetResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MyController extends AbstractController {
    #[Route("/download", name: "download_spreadsheet")]
    public function download(): SpreadsheetResponse {
        $spreadsheet = new Spreadsheet();
        // build the spreadsheet...
        return new SpreadsheetResponse($spreadsheet, "report.xlsx");
    }
}
```

### Export as CSV

```php
use PhpOffice\PhpSpreadsheet\Writer\Csv;

return new SpreadsheetResponse(
    $spreadsheet,
    "export.csv",
    writer: new Csv($spreadsheet)
);
```

### Display inline (in browser)

```php
return new SpreadsheetResponse(
    $spreadsheet,
    "preview.xlsx",
    disposition: 'inline'
);
```


## Installation
This library is available through [Packagist](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response) and can be installed using [Composer](https://getcomposer.org/): 

```bash
composer require codeinc/symfony-phpspreadsheet-response
```


## License
This library is published under the MIT license (see the [LICENSE](LICENSE) file). 

