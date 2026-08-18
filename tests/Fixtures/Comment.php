<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'test_comments';

    protected $guarded = ['id'];
}
