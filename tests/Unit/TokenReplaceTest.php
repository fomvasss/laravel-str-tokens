<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Unit;

use Carbon\Carbon;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\Order;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\User;
use Fomvasss\LaravelStrTokens\Tests\TestCase;

class TokenReplaceTest extends TestCase
{
    public function test_setEntity_resolves_plain_field_token(): void
    {
        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $result = $this->tokens()->setText('Title: [order:title]')->setEntity($order)->replace();

        $this->assertSame('Title: Order #1', $result);
    }

    public function test_setEntities_resolves_multiple_entities_by_key(): void
    {
        $user = User::create(['name' => 'John', 'lastname' => 'Doe']);
        $order = Order::create(['title' => 'Order #1']);

        $result = $this->tokens()
            ->setText('[user:name] ordered [order:title]')
            ->setEntities(['user' => $user, 'order' => $order])
            ->replace();

        $this->assertSame('John ordered Order #1', $result);
    }

    public function test_setEntities_clears_a_previously_set_setEntity(): void
    {
        $order = Order::create(['title' => 'Order #1']);
        $user = User::create(['name' => 'John']);

        // Раніше виставлений setEntity($order) не повинен впливати після setEntities([...])
        $result = $this->tokens()
            ->setEntity($order)
            ->setEntities(['user' => $user])
            ->setText('[user:name] / [order:title]')
            ->replace();

        // [order:title] більше не резолвиться — order-entity скинуто
        $this->assertSame('John / ', $result);
    }

    public function test_var_tokens(): void
    {
        $result = $this->tokens()
            ->setVars(['siteName' => 'Acme'])
            ->setVar('year', 2026)
            ->setText('[var:siteName] © [var:year]')
            ->replace();

        $this->assertSame('Acme © 2026', $result);
    }

    public function test_date_tokens(): void
    {
        $result = $this->tokens()
            ->setDate(Carbon::create(2026, 1, 15, 10, 30))
            ->setText('[date:date]')
            ->replace();

        $this->assertSame('15.01.2026', $result);
    }

    public function test_unresolved_token_is_cleared_by_default(): void
    {
        $order = Order::create(['title' => 'Order #1']);

        $result = $this->tokens()->setText('[order:unknownField]')->setEntity($order)->replace();

        // unknownField не колонка й не метод — Eloquent поверне null для неіснуючого атрибута
        $this->assertSame('', $result);
    }

    public function test_doNotClearEmptyTokens_keeps_literal_token_text(): void
    {
        // [var:...]/[date:...]/[config:...] завжди підставляють значення (за потреби — '')
        // самі, незалежно від clearEmptyTokens — прапорець стосується лише токена типу,
        // що не збігається взагалі ні з чим (нема ні такої entity, ні var/date/config)
        $result = $this->tokens()
            ->doNotClearEmptyTokens()
            ->setText('Hello [foo:bar]')
            ->replace();

        $this->assertSame('Hello [foo:bar]', $result);
    }

    public function test_unmatched_token_type_is_cleared_by_default(): void
    {
        $result = $this->tokens()->setText('Hello [foo:bar]')->replace();

        $this->assertSame('Hello ', $result);
    }
}
