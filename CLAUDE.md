# Project Guidelines

## Testing

**Always run tests before committing changes:**

```bash
./vendor/bin/phpunit
```

## Project Structure

- `src/` - Library source code (single class: `SpreadsheetResponse`)
- `tests/` - PHPUnit tests

## Requirements

- PHP 8.1+
- Dependencies: Symfony HttpFoundation, PHPSpreadsheet

## CI

GitHub Actions runs tests on PHP 8.1-8.4 with both lowest and highest dependency versions. PRs must pass all CI checks.

## Code Style

- PSR-4 autoloading
- Strict types enabled
- Keep the library minimal and focused
