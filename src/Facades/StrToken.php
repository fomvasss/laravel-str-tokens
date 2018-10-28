<?php

namespace Fomvasss\LaravelStrTokens\Facades;

use Fomvasss\LaravelStrTokens\StrTokenGenerator;
use Illuminate\Support\Facades\Facade;

class StrToken extends Facade
{
    public static function getFacadeAccessor()
    {
        return StrTokenGenerator::class;
    }
}