<?php

namespace Fomvasss\LaravelStrTokens\Formatters;

class ClearHtmlFormatter
{
    public function handle(string|null $str): string
    {
        if ($str) {
            return strip_tags($str);
        }
        
        return '';
    }
}