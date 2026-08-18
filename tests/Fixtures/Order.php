<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Fixtures;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'test_orders';

    protected $guarded = ['id'];

    protected $casts = [
        'extra' => 'array',
    ];

    // Мутуються тестами для strTokenWhitelist()/strTokenBlacklist(), скидаються в tearDown()
    public static array $tokenWhitelist = [];
    public static array $tokenBlacklist = [];

    // Ім'я relation НЕ збігається з class_basename пов'язаної моделі (User) — реальний
    // сценарій, що ламав вкладені токени до фіксу matchAnyKey
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Той самий кейс, що й Chat::lastChannel() в itschats — relation "lastChannel" на клас "Channel"
    public function lastChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'last_channel_id');
    }

    // Collection-relation з тим самим mismatch (comments -> Comment)
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'order_id');
    }

    // Laravel 9+ атрибут-аксесор, що раніше плутався з нерезолвленим relation
    // (method_exists() не відрізняє accessor-метод від справжнього relation-методу)
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->title} [{$this->status}]",
        );
    }

    public function strTokenStatus(): string
    {
        return "Status: {$this->status}";
    }

    // Приклад параметризованого токена: [order:total] чи [order:total:EUR]
    public function strTokenTotal(self $order, string $key, string $currency = 'USD'): string
    {
        return "{$order->title} ({$currency})";
    }

    public function strTokenWhitelist(): array
    {
        return static::$tokenWhitelist;
    }

    public function strTokenBlacklist(): array
    {
        return static::$tokenBlacklist;
    }
}
