<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests\Unit;

use Fomvasss\LaravelStrTokens\Tests\Fixtures\User;
use Fomvasss\LaravelStrTokens\Tests\TestCase;

// Динамічні/обчислювані поля (не колонки БД) — два стилі Eloquent-аксесорів, обидва
// читаються звичайним property-синтаксисом ($model->x), тож для пакета це виглядає
// як "просто ще один атрибут": формате, вкладена трансляція тощо працюють так само,
// як і для реальної колонки.
class AccessorsTest extends TestCase
{
    public function test_new_style_attribute_accessor(): void
    {
        // protected function fullname(): Attribute — метод зветься так само, як
        // властивість, тому проходить через accessor-fallback гілку пакета
        $user = User::create(['name' => 'John', 'lastname' => 'Doe']);

        $result = $this->tokens()->setText('[user:fullname]')->setEntity($user)->replace();

        $this->assertSame('John Doe', $result);
    }

    public function test_old_style_getAttribute_accessor(): void
    {
        // getInitialsAttribute() — метод зветься інакше, ніж властивість "initials",
        // тому method_exists($model, 'initials') === false і йде проста "field"-гілка
        $user = User::create(['name' => 'John', 'lastname' => 'Doe']);

        $result = $this->tokens()->setText('[user:initials]')->setEntity($user)->replace();

        $this->assertSame('JD', $result);
    }

    public function test_formatter_applies_to_a_computed_accessor_too(): void
    {
        $user = User::create(['name' => 'john', 'lastname' => 'doe']);

        $result = $this->tokens()->setText('[user:fullname:uppercase]')->setEntity($user)->replace();

        $this->assertSame('JOHN DOE', $result);
    }
}
