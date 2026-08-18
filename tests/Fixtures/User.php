<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Fixtures;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

// Клас навмисно зветься "User" (не "TestUser") — тип токена [user:...] резолвиться
// через Str::snake(class_basename()), тому назва класу має збігатись із префіксом токена
class User extends Model
{
    protected $table = 'test_users';

    protected $guarded = ['id'];

    // Навмисно без strTokenFullname() — резолв [user:fullname] має пройти через
    // generic accessor fallback, не через явний override
    protected function fullname(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->name . ' ' . $this->lastname),
        );
    }

    // Старий (pre-Laravel-9) стиль аксесора — інший код-шлях у пакеті: метод не зветься
    // так само, як властивість (getXAttribute(), не x()), тому method_exists($model,'initials')
    // повертає false, і токен йде напряму у фінальну "field"-гілку ($model->{$field}), яка все
    // одно коректно тригерить Eloquent __get()/аксесор — без потреби в accessor-fallback фіксі
    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->name, 0, 1) . mb_substr((string) $this->lastname, 0, 1));
    }
}
