<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Unit;

use Fomvasss\LaravelStrTokens\Tests\Fixtures\Order;
use Fomvasss\LaravelStrTokens\Tests\Fixtures\User;
use Fomvasss\LaravelStrTokens\Tests\TestCase;
use InvalidArgumentException;

class FormattersTest extends TestCase
{
    public function test_uppercase_formatter(): void
    {
        $user = User::create(['name' => 'john']);

        $result = $this->tokens()->setText('[user:name:uppercase]')->setEntity($user)->replace();

        $this->assertSame('JOHN', $result);
    }

    public function test_lowercase_formatter(): void
    {
        $user = User::create(['name' => 'JOHN']);

        $result = $this->tokens()->setText('[user:name:lowercase]')->setEntity($user)->replace();

        $this->assertSame('john', $result);
    }

    public function test_trim_formatter(): void
    {
        $user = User::create(['name' => '  John  ']);

        $result = $this->tokens()->setText('[user:name:trim]')->setEntity($user)->replace();

        $this->assertSame('John', $result);
    }

    public function test_clearHtml_formatter(): void
    {
        $user = User::create(['name' => '<b>John</b>']);

        $result = $this->tokens()->setText('[user:name:clearHtml]')->setEntity($user)->replace();

        $this->assertSame('John', $result);
    }

    public function test_urlLink_formatter(): void
    {
        $user = User::create(['email' => 'john@example.com']);

        $result = $this->tokens()->setText('[user:email:urlLink]')->setEntity($user)->replace();

        $this->assertSame("<a href='john@example.com'>john@example.com</a>", $result);
    }

    // Регресія на strict_types=1: id — int-колонка (не string). Формате (built-in `trim`, і
    // будь-який клас з handle(string|null)) раніше отримував це значення з мовчазною коерсією
    // int -> string; зі strict_types виклик без явного cast впав би TypeError
    public function test_formatter_applies_to_a_non_string_scalar_field(): void
    {
        $order = Order::create(['title' => 'Order #1']);

        $result = $this->tokens()->setText('[order:id:trim]')->setEntity($order)->replace();

        $this->assertSame((string) $order->id, $result);
    }

    // Регресія: "trim" => 'trim' (рядок-назва функції) резолвиться через is_callable() ще до
    // перевірки class_exists() — тому "справжній" InvalidArgumentException-шлях (formatter, що не
    // callable, не FQCN і не ім'я функції) раніше падав фатальною Class-not-found-помилкою,
    // бо клас використовувався без `use InvalidArgumentException;`
    public function test_unresolvable_formatter_throws_invalid_argument_exception(): void
    {
        config(['str-tokens.formatters.broken' => 'Not\\A\\Real\\Class']);

        $user = User::create(['name' => 'John']);

        $this->expectException(InvalidArgumentException::class);

        $this->tokens()->setText('[user:name:broken]')->setEntity($user)->replace();
    }

    // Регресія: "0" — falsy в PHP (`if ($str)`), тому built-in формате мовчки повертали ''
    // замість обробленого "0" для будь-якого поля з таким рядковим значенням
    public function test_formatters_do_not_treat_the_string_zero_as_empty(): void
    {
        $user = User::create(['name' => '0']);

        $this->assertSame('0', $this->tokens()->setText('[user:name:uppercase]')->setEntity($user)->replace());
        $this->assertSame('0', $this->tokens()->setText('[user:name:lowercase]')->setEntity($user)->replace());
        $this->assertSame('0', $this->tokens()->setText('[user:name:trim]')->setEntity($user)->replace());
        $this->assertSame('0', $this->tokens()->setText('[user:name:clearHtml]')->setEntity($user)->replace());
    }

    // Регресія: значення не екранувалось перед вставкою в href='...' — лапка в полі
    // ламала атрибут і дозволяла інʼєкцію довільного HTML/атрибута в результуючий тег
    public function test_urlLink_formatter_escapes_the_value(): void
    {
        $user = User::create(['email' => "x' onmouseover=alert(1) y='"]);

        $result = $this->tokens()->setText('[user:email:urlLink]')->setEntity($user)->replace();

        // Лапка екранована (&#039;) — значення більше не розриває атрибут href='...'
        $this->assertSame(
            "<a href='x&#039; onmouseover=alert(1) y=&#039;'>x&#039; onmouseover=alert(1) y=&#039;</a>",
            $result
        );
    }

    // Регресія: non-scalar значення (JSON-cast колонка -> array) без форматера долітало до
    // фінального str_replace() і PHP мовчки перетворювало його на літерал "Array"
    public function test_non_scalar_field_value_is_cleared_instead_of_the_literal_array_string(): void
    {
        $order = Order::create(['title' => 'Order #1', 'extra' => ['a' => 1]]);

        $result = $this->tokens()->setText('Extra: [order:extra]')->setEntity($order)->replace();

        $this->assertSame('Extra: ', $result);
    }

    public function test_custom_closure_formatter(): void
    {
        config(['str-tokens.formatters.reverse' => fn ($v) => strrev((string) $v)]);

        $user = User::create(['name' => 'John']);

        $result = $this->tokens()->setText('[user:name:reverse]')->setEntity($user)->replace();

        $this->assertSame('nhoJ', $result);
    }

    // Регресія: формате матчиться точно по суфіксу після останнього ":", не підрядком.
    // Токен [order:assignedUser:name] — "name" тут ІМ'Я ПОЛЯ пов'язаної моделі (звичайна
    // колонка, не formatter-суфікс), але той самий tail-рядок ("name") перевіряється
    // формате-циклом і в зовнішньому виклику eloquentModelTokens() теж (він застосовується
    // до $key незалежно від того, яка гілка резолвила значення). Раніше Str::contains()
    // спрацьовувала б на будь-якому formatter, чиє ім'я — підрядок "name" (напр. "ame")
    public function test_formatter_does_not_match_by_substring_of_a_nested_field_name(): void
    {
        config(['str-tokens.formatters.ame' => fn ($v) => 'OOPS-' . $v]);

        $user = User::create(['name' => 'John']);
        $order = Order::create(['title' => 'Order #1', 'user_id' => $user->id]);

        $result = $this->tokens()->setText('[order:assignedUser:name]')->setEntity($order)->replace();

        $this->assertSame('John', $result);
    }

    public function test_formatter_still_matches_when_suffix_is_exact(): void
    {
        config(['str-tokens.formatters.name' => fn ($v) => 'OOPS-' . $v]);

        $user = User::create(['name' => 'John']);
        $order = Order::create(['title' => 'Order #1', 'user_id' => $user->id]);

        // тут formatter "name" точно збігається з tail "name" — має спрацювати
        $result = $this->tokens()->setText('[order:assignedUser:name]')->setEntity($order)->replace();

        $this->assertSame('OOPS-John', $result);
    }
}
