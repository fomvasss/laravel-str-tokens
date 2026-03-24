<?php

namespace Fomvasss\LaravelStrTokens\Formatters;

class LoverrcaseFormatter
{
    public function handle(string|null $str): string
    {
        if ($str) {
            return  mb_strtolower($str, 'UTF-8');
        }
        
        return '';
    }
}