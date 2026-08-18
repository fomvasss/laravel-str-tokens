<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Formatters;

class UppercaseFormatter
{
    public function handle(string|null $str): string
    {
        // === null, не truthy-перевірка — рядок "0" є falsy в PHP і мовчки губився б
        if ($str === null) {
            return '';
        }

        return mb_strtoupper($str, 'UTF-8');
    }
}
