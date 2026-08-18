<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'test_channels';

    protected $guarded = ['id'];
}
