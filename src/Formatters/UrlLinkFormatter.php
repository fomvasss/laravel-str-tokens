<?php

namespace Fomvasss\LaravelStrTokens\Formatters;

class UrlLinkFormatter
{
    public function handle(string|null $str): string
    {
        if ($str) {
            return "<a href='{$str}'>{$str}</a>";
        }
        
        return '';
    }
}