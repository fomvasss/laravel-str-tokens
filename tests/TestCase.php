<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Tests;

use Fomvasss\LaravelStrTokens\ServiceProvider;
use Fomvasss\LaravelStrTokens\StrTokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    // Свіжий інстанс на кожен виклик — StrTokenGenerator зареєстрований як singleton
    // (ServiceProvider::register()), тому фасад StrToken:: ділив би внутрішній стан
    // (clearEmptyTokens тощо) між тестами
    protected function tokens(): StrTokenGenerator
    {
        return new StrTokenGenerator($this->app);
    }

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['StrToken' => \Fomvasss\LaravelStrTokens\Facades\StrToken::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createFixtureTables();
    }

    // Схема фікстур — inline Schema::create(), пакет не постачає власних міграцій
    // (це виключно тестові моделі, не частина публічного API)
    private function createFixtureTables(): void
    {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('test_channels', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('test_orders', function ($table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('last_channel_id')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        Schema::create('test_comments', function ($table) {
            $table->id();
            $table->foreignId('order_id');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }
}
