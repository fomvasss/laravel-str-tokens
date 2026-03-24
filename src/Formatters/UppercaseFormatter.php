<?php

namespace Fomvasss\LaravelStrTokens\Formatters;

class UppercaseFormatter
{
    public function handle(string|null $str): string
    {
        if ($str) {
            return mb_strtoupper($str, 'UTF-8');
        }
        
        return '';
    }
}