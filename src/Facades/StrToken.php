<?php

declare(strict_types=1);

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