# Symfony PhpSpreadsheet HTTP response

A simple Symfony HTTP response for PhpSpreadsheet written in PHP 8 intended to the used with Symfony's
[HTTPFoundation component](https://symfony.com/components/HttpFoundation).

## Usage

```php
<?php
use CodeInc\SpreadsheetResponse\SpreadsheetResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;

class MyController extends AbstractController {
    #[Route("/a-spreadsheet", name: "download_a_spreadsheet")]
    public function downloadASpreadsheet(): Response {
        $spreadsheet = new Spreadsheet();
        // building the spreadsheet...
        return new SpreadsheetResponse($spreadsheet, "A spreadsheet.xlsx");
    }
}
```


## Installation
This library is available through [Packagist](https://packagist.org/packages/codeinc/symfony-phpspreadsheet-response) and can be installed using [Composer](https://getcomposer.org/): 

```bash
composer require codeinc/symfony-phpspreadsheet-response
```


## License
This library is published under the MIT license (see the [LICENSE](LICENSE) file). 

