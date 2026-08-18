<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Unit;

use Fomvasss\LaravelStrTokens\Tests\Fixtures\Channel;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\Comment;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\Order;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\User;
use Fomvasss\LaravelStrTokens\Tests\TestCase;

// Регресійні тести на баг: вкладений токен через relation, чиє ім'я НЕ збігається з
// class_basename пов'язаної моделі (напр. lastChannel() -> Channel), раніше мовчки
// резолвився в порожній рядок — replace() матчив вкладену сутність лише коли
// strtolower($key) === Str::snake(class_basename($entity))
class RelationTraversalTest extends TestCase
{
    public function test_nested_belongsTo_token_resolves_when_relation_name_matches_class(): void
    {
        $user = User::create(['name' => 'John', 'lastname' => 'Doe']);
        $order = Order::create(['title' => 'Order #1', 'user_id' => $user->id]);

        $result = $this->tokens()->setText('[order:assignedUser:name]')->setEntity($order)->replace();

        $this->assertSame('John', $result);
    }

    public function test_nested_belongsTo_token_resolves_when_relation_name_differs_from_class(): void
    {
        $channel = Channel::create(['name' => 'Telegram Bot']);
        $order = Order::create(['title' => 'Order #1', 'last_channel_id' => $channel->id]);

        // lastChannel() -> Channel::class: "lastchannel" !== snake("Channel") = "channel"
        $result = $this->tokens()->setText('[order:lastChannel:name]')->setEntity($order)->replace();

        $this->assertSame('Telegram Bot', $result);
    }

    public function test_nested_belongsTo_token_on_a_multi_word_accessor_field(): void
    {
        $user = User::create(['name' => 'John', 'lastname' => 'Doe']);
        $order = Order::create(['title' => 'Order #1', 'user_id' => $user->id]);

        // fullname — Attribute-аксесор на User, не колонка
        $result = $this->tokens()->setText('[order:assignedUser:fullname]')->setEntity($order)->replace();

        $this->assertSame('John Doe', $result);
    }

    public function test_nested_hasMany_token_resolves_first_related_model(): void
    {
        $order = Order::create(['title' => 'Order #1']);
        Comment::create(['order_id' => $order->id, 'body' => 'First comment']);
        Comment::create(['order_id' => $order->id, 'body' => 'Second comment']);

        // comments() -> Comment::class: "comments" !== snake("Comment") = "comment"
        $result = $this->tokens()->setText('[order:comments:body]')->setEntity($order)->replace();

        $this->assertSame('First comment', $result);
    }

    public function test_null_relation_resolves_to_empty_string_without_error(): void
    {
        $order = Order::create(['title' => 'Order #1']); // без user_id/last_channel_id

        $result = $this->tokens()->setText('Manager: [order:assignedUser:name]')->setEntity($order)->replace();

        $this->assertSame('Manager: ', $result);
    }

    public function test_attribute_accessor_is_not_mistaken_for_an_unresolved_relation(): void
    {
        // displayName() — protected function displayName(): Attribute, не relation.
        // method_exists() не відрізняє одне від іншого — без accessor-fallback токен
        // мовчки резолвився б у ''
        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $result = $this->tokens()->setText('[order:displayName]')->setEntity($order)->replace();

        $this->assertSame('Order #1 [active]', $result);
    }

    // Регресія: з can_traverse_relations=false токен, що все одно називає relation, не падає
    // в "field"-гілку впритул до Eloquent-моделі — Model має власний __toString() (toJson()),
    // тому без guard'а результат був би повним JSON-дампом моделі, а не порожнім рядком
    public function test_relation_named_token_resolves_to_empty_string_when_traversal_disabled(): void
    {
        config(['str-tokens.can_traverse_relations' => false]);

        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $order = Order::create(['title' => 'Order #1', 'user_id' => $user->id]);

        $result = $this->tokens()->setText('Manager: [order:assignedUser:email]')->setEntity($order)->replace();

        $this->assertSame('Manager: ', $result);
    }

    public function test_hasMany_relation_named_token_resolves_to_empty_string_when_traversal_disabled(): void
    {
        config(['str-tokens.can_traverse_relations' => false]);

        $order = Order::create(['title' => 'Order #1']);
        Comment::create(['order_id' => $order->id, 'body' => 'First comment']);

        $result = $this->tokens()->setText('[order:comments:body]')->setEntity($order)->replace();

        $this->assertSame('', $result);
    }
}
