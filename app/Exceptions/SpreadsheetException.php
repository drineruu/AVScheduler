<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class SpreadsheetException extends RuntimeException
{
    public static function missingConfiguration(string $key): self
    {
        return new self("Google Sheets configuration [{$key}] is not set.");
    }

    public static function invalidCredentials(): self
    {
        return new self('Google service account credentials are invalid or unreadable.');
    }

    public static function apiFailure(string $message, ?Throwable $previous = null): self
    {
        return new self("Google Sheets API error: {$message}", 0, $previous);
    }

    public static function malformedRow(string $sheetName, string $details): self
    {
        return new self("Malformed row in [{$sheetName}] sheet: {$details}");
    }

    public static function missingColumn(string $sheetName, string $column): self
    {
        return new self("Required column [{$column}] is missing from [{$sheetName}] sheet.");
    }

    public static function notFound(string $resource, string $identifier): self
    {
        return new self("{$resource} [{$identifier}] was not found.");
    }

    public static function duplicate(string $resource, string $identifier): self
    {
        return new self("{$resource} [{$identifier}] already exists.");
    }

    public static function invalidReference(string $details): self
    {
        return new self("Invalid reference: {$details}");
    }
}
