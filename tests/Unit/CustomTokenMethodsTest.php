<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Unit;

use Fomvasss\LaravelStrTokens\Tests\Fixtures\Order;
use Fomvasss\LaravelStrTokens\Tests\TestCase;

class CustomTokenMethodsTest extends TestCase
{
    protected function tearDown(): void
    {
        Order::$tokenWhitelist = [];
        Order::$tokenBlacklist = [];

        parent::tearDown();
    }

    public function test_custom_strToken_method_without_params(): void
    {
        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $result = $this->tokens()->setText('[order:status]')->setEntity($order)->replace();

        $this->assertSame('Status: active', $result);
    }

    public function test_custom_strToken_method_receives_extra_colon_separated_params(): void
    {
        $order = Order::create(['title' => 'Order #1']);

        $default = $this->tokens()->setText('[order:total]')->setEntity($order)->replace();
        $withParam = $this->tokens()->setText('[order:total:EUR]')->setEntity($order)->replace();

        $this->assertSame('Order #1 (USD)', $default);
        $this->assertSame('Order #1 (EUR)', $withParam);
    }

    public function test_strTokenWhitelist_restricts_exposed_tokens(): void
    {
        Order::$tokenWhitelist = ['title'];

        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $title = $this->tokens()->setText('[order:title]')->setEntity($order)->replace();
        $status = $this->tokens()->setText('[order:status]')->setEntity($order)->replace();

        $this->assertSame('Order #1', $title);
        $this->assertSame('', $status);
    }

    public function test_strTokenBlacklist_blocks_specific_tokens(): void
    {
        Order::$tokenBlacklist = ['status'];

        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $title = $this->tokens()->setText('[order:title]')->setEntity($order)->replace();
        $status = $this->tokens()->setText('[order:status]')->setEntity($order)->replace();

        $this->assertSame('Order #1', $title);
        $this->assertSame('', $status);
    }

    public function test_disable_model_tokens_config_blocks_globally(): void
    {
        config(['str-tokens.disable_model_tokens' => ['status']]);

        $order = Order::create(['title' => 'Order #1', 'status' => 'active']);

        $title = $this->tokens()->setText('[order:title]')->setEntity($order)->replace();
        $status = $this->tokens()->setText('[order:status]')->setEntity($order)->replace();

        $this->assertSame('Order #1', $title);
        $this->assertSame('', $status);
    }
}
