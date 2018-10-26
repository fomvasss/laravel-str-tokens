<?php

namespace Fomvasss\LaravelStrTokens;

use Illuminate\Support\Facades\Facade as LFacade;

class Facade extends LFacade
{
    public static function getFacadeAccessor()
    {
        return StrTokenGenerator::class;
    }
}