# Laravel Str Tokens

Replace `[type:field]`-style tokens/shortcodes in a string with real values pulled from Eloquent models, config, dates, or arbitrary variables — the same idea as Drupal's token system, built for Laravel.

[![PHP](https://img.shields.io/badge/PHP-8.0.2%2B-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-9--13-red)](https://laravel.com/)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-str-tokens.svg)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-str-tokens.svg)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![License](https://img.shields.io/packagist/l/fomvasss/laravel-str-tokens.svg)](LICENSE.md)

---

## Why

Any app that builds text from a template ends up writing the same glue code: notification/email bodies with
placeholders, admin-configurable message templates, CMS-style content blocks, SMS text with client data,
generated documents. This package turns that into one small, declarative API: write `[order:title]` in a
string, hand it an `Order` model, get the real title back — including nested relations, custom formatting
rules, and values that have nothing to do with a model at all (dates, config, arbitrary variables).

## Features

- `[type:field]` token resolution against **any Eloquent model** — no base class, no interface required
- **Nested relations**: `[order:manager:email]` walks the `manager()` relation and resolves `email` on the result, to any depth
- **Custom tokens** per model via `strToken*()` methods — full control, optional extra arguments (`[chat:message:100]`)
- **Formatters**: `[user:name:uppercase]` post-processes the resolved value; ships with `uppercase`, `lowercase`, `trim`, `clearHtml`, `urlLink`, plus your own
- **Multiple entities** in one string via `setEntities(['user' => $user, 'order' => $order])`
- Standalone token namespaces that don't need any model: `[var:...]`, `[date:...]`, `[config:...]`
- **Access control**: per-model `strTokenWhitelist()` / `strTokenBlacklist()`, or a global `disable_model_tokens` / `disable_configs` config list — keep secrets like `password` or `api_token` out of reach even if a template author tries
- Configurable token syntax (`token_split_character`, `token_match_pattern`) if `[type:field]` doesn't fit your project
- Ships as a Facade (`StrToken`) and a container-resolvable class (`StrTokenGenerator`)

## Requirements

- PHP ^8.0.2
- Laravel (`illuminate/support`) ^9 – ^13

## Installation

```bash
composer require fomvasss/laravel-str-tokens
```

The service provider and `StrToken` facade are auto-discovered. To customize the config, publish it:

```bash
php artisan vendor:publish --provider="Fomvasss\LaravelStrTokens\ServiceProvider"
```

This creates `config/str-tokens.php`.

## Quick example

```php
use Fomvasss\LaravelStrTokens\Facades\StrToken;

$text = <<<TXT
Order: [order:title] (#[order:id])
Status: [order:status]
Manager: [order:manager:fullname] <[order:manager:email]>
Channel: [order:channel:name]
Support line: [var:supportPhone]
Today: [date:date]
TXT;

$result = StrToken::setText($text)
    ->setEntity($order)
    ->setVar('supportPhone', '+380 44 000 0000')
    ->replace();
```

```text
Order: Order #1042 (#1)
Status: Status: active
Manager: Taylor Otwell <taylor@laravel.com>
Channel: Telegram
Support line: +380 44 000 0000
Today: 17.08.2026
```

## Core concepts

### Token syntax

A token is `[type:name]`. `type` identifies *what* to resolve against (a model, or one of the built-in
namespaces below); `name` is everything after the first `:` — it may contain further colons, which is how
nested relations and formatters are expressed (`manager:email`, `name:uppercase`).

### One entity: `setEntity()`

```php
StrToken::setText('[order:title], placed by [order:manager:fullname]')
    ->setEntity($order)
    ->replace();
```

The token `type` must match `Str::snake(class_basename($entity))` — an `App\Models\Order` instance is
addressed as `[order:...]`, an `App\Models\BlogPost` as `[blog_post:...]`. If your token prefix can't (or
shouldn't) match the class name, use `setEntities()` instead — its keys are whatever you want them to be.

### Multiple entities: `setEntities()`

```php
StrToken::setText('Manager: [manager:fullname], Client: [client:fullname], Order: [order:title]')
    ->setEntities([
        'manager' => $manager,
        'client' => $client,
        'order' => $order,
    ])
    ->replace();

// Manager: Taylor Otwell, Client: Vasyl Fomin, Order: Order #1042
```

The array key is the token prefix — it doesn't need to relate to the model's class name at all.

> **Don't combine `setEntity()` and `setEntities()`.** Calling `setEntities()` clears whatever `setEntity()`
> set before it; calling `setEntity()` after `setEntities()` does *not* clear `setEntities()`, and `setEntity()`
> takes priority. Pick one per `replace()` call.

### Nested relations

Any real Eloquent relation method on the entity can be walked, to any depth — the relation method's *name*
does not need to match the related model's class name:

```php
// Order::manager() is a belongsTo(User::class) — name doesn't matter
'[order:manager:fullname]'

// walks two relations deep
'[order:manager:company:name]'

// a hasMany/BelongsToMany relation resolves against its FIRST related model
'[order:comments:body]'
```

A relation that resolves to `null` (e.g. an unassigned `belongsTo`) simply produces an empty string —
no error.

Set `can_traverse_relations => false` in the config to disable this globally; a nested token then resolves
to an empty string instead of walking the relation (see [Configuration](#configuration)).

### Standalone tokens — no model required

| Token | Resolves to |
|---|---|
| `[var:name]` | A value set via `setVar('name', $value)` / `setVars([...])`. Missing key → `''`. |
| `[date:format]` | The date set via `setDate($carbon)` (defaults to `now()`), formatted using `date.formats.{format}` from the config. `[date:raw]` returns the `Carbon` instance itself. |
| `[config:some.key]` | `config('some.key')`, unless it matches a `disable_configs` pattern. |

```php
StrToken::setText('[var:price] — offer valid until [date:long] — app: [config:app.name]')
    ->setDate(now()->addDays(3))
    ->setVar('price', '$49')
    ->replace();
```

## Custom tokens: `strToken*()` methods

Define a method named `Str::camel('str_token_' . $name)` on the model to fully control how a token
resolves — e.g. `[order:status]` looks for `strTokenStatus()`. This takes priority over relation traversal
and plain field access.

```php
class Order extends Model
{
    // [order:status] -> "Status: active"
    public function strTokenStatus(): string
    {
        return 'Status: ' . $this->status;
    }
}
```

**Extra arguments** — every `:`-separated segment of the token, including the method's own name, is passed
through as a string argument after the model itself. This is how you build a parameterized token:

```php
class Chat extends Model
{
    // [chat:lastMessage]      -> full text
    // [chat:lastMessage:100]  -> first 100 characters
    public function strTokenLastMessage(self $chat, string $key, ?string $limit = null): string
    {
        $text = strip_tags((string) $chat->lastMessage?->content);

        return $limit ? \Illuminate\Support\Str::limit($text, (int) $limit) : $text;
    }
}
```

Arguments always arrive as strings (they come from exploding the raw token text) — type-hint them as
`?string` and cast inside the method, not as `int`/`float`, or a strict-typed parameter will throw.

## Formatters

Append `:formatterName` as the *last* segment of a token to post-process its resolved value:

```php
'[user:name:uppercase]'          // "JOHN"
'[user:email:lowercase]'
'[user:bio:clearHtml]'           // strip_tags()
'[user:website:urlLink]'         // wraps into <a href="...">...</a>, value is HTML-escaped
```

Built-in formatters (`config('str-tokens.formatters')`):

| Key | Effect |
|---|---|
| `trim` | PHP's `trim()` |
| `uppercase` | `mb_strtoupper()` |
| `lowercase` | `mb_strtolower()` |
| `clearHtml` | `strip_tags()` |
| `urlLink` | Wraps the (HTML-escaped) value in `<a href='...'>...</a>` |

Register your own — a formatter is any callable, an invokable class with a `handle(?string $value): string`
method, or a global function name:

```php
// config/str-tokens.php
'formatters' => [
    // ...defaults...
    'money' => fn (?string $v) => number_format((float) $v, 2) . ' UAH',
],
```

```php
'[order:total:money]'
```

**Only one formatter per token** — `[user:name:uppercase:trim]` does not chain both; the *last* segment
after the final `:` is looked up as-is. If you need to combine several transforms, write one custom
`strToken*()` method that does both (see the `[chat:lastMessage:100]` example above) rather than trying to
stack formatters.

## Restricting what's exposed

Templates are often edited by someone other than a developer (an admin UI, a client-configurable
notification). Three independent ways to keep sensitive fields out of reach:

```php
class User extends Model
{
    // Only these tokens resolve for this model — everything else is silently empty
    public function strTokenWhitelist(): array
    {
        return ['name', 'email'];
    }

    // Or: block specific ones, allow everything else
    public function strTokenBlacklist(): array
    {
        return ['password', 'remember_token', 'api_token'];
    }
}
```

```php
// config/str-tokens.php — applies to every model
'disable_model_tokens' => ['*password*', '*token*', '*secret*'],

// blocks [config:...] lookups the same way
'disable_configs' => ['app.key', 'auth.*', 'mail.*', 'services.*', 'password', '*token*'],
```

All four accept [`Str::is()`](https://laravel.com/docs/strings#method-str-is) glob patterns (`*` wildcards),
and are checked against the *full* token name — so `manager:*` blocks every field of that relation in
one line.

## Configuration

Everything below lives in `config/str-tokens.php` after publishing it; all keys are optional.

| Key | Default | Purpose |
|---|---|---|
| `token_split_character` | `:` | Separator inside a token name (`type:a:b:c`) |
| `token_match_pattern` | `/\[([^\s\[\]:]*):([^\[\]]*)\]/x` | The whole token regex — override to use a different bracket style entirely |
| `can_traverse_relations` | `true` | Set `false` to disable nested-relation resolution globally |
| `disable_model_tokens` | `[]` | Glob patterns blocked on every model |
| `disable_configs` | see file | Glob patterns blocked for `[config:...]` |
| `date.formats` | `short`, `medium`, `long`, `time`, `date`, `my` | Named formats for `[date:name]`; add your own key/format pair |
| `formatters` | `trim`, `uppercase`, `lowercase`, `clearHtml`, `urlLink` | See [Formatters](#formatters) |

## Use cases

**Admin-configurable notification templates.** Store the template text (mail subject/body, SMS text) in the
DB, let an admin edit it with a list of available tokens, resolve it against the real model when the
notification actually sends:

```php
$template = NotificationTemplate::where('key', 'OrderShipped')->first();

$body = StrToken::setText($template->body)
    ->setEntity($order)
    ->replace();
```

**Blade.**

```blade
@php($body = \StrToken::setEntity($article)->setDate($article->created_at)
    ->setText('[article:title] — published [date:short]')
    ->replace())
<h3>{!! $body !!}</h3>
```

**Building an SMS/email body with a length cap** — combine a custom `strToken*()` method with its own
parameter instead of relying on formatter chaining (see [Custom tokens](#custom-tokens-strtoken-methods)).

**Multi-tenant/CMS content blocks** where several unrelated records feed one string — use `setEntities()`
with descriptive keys instead of forcing everything onto one model's relations.

## Behavior notes

- An unresolved token becomes `''` by default. Call `->doNotClearEmptyTokens()` to leave *genuinely unmatched*
  token types as literal text instead — this only applies to a token type that doesn't match any known
  entity/`var`/`date`/`config` at all; `[var:missingKey]` still resolves to `''` either way, since the `var`
  namespace always produces a value (empty if the key is missing).
- A resolved value that isn't a string, number, bool, or `Stringable` object (e.g. a JSON-cast array column
  read without a formatter) resolves to `''` rather than the literal text `Array` or a fatal error.
- `[date:raw]` returns the actual `Carbon` instance, not a formatted string — useful when you want to pass
  it on to more Carbon methods before printing it yourself.

## Testing

```bash
composer test
# or
vendor/bin/phpunit
```

## Related

- [laravel-simple-taxonomy](https://github.com/fomvasss/laravel-simple-taxonomy) — if a model uses it, its
  taxonomy relations are reachable via the `tx` prefix convention (`[article:txCategories:name]`); not
  required otherwise.

## License

MIT — see [LICENSE](LICENSE.md).

## Support

If this package is useful to you, consider supporting its development:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> USDT TRC20 address: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`
