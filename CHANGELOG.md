# Changelog

All notable changes to `fomvasss/laravel-str-tokens` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

## [2.8.0] - 2026-08-17

### Changed
- Type coercion into `formatters` and custom `strToken*()` methods is now strict. The package's own built-in calls are unaffected (values are cast explicitly first), but a custom formatter or `strToken*()` method with a strictly-typed non-string parameter (e.g. `int`) may now need to accept `string` instead, since arguments always arrive as strings
- Minimum PHP raised to `^8.0.2` (previously declared `>=7.0.0`, which did not reflect actual compatibility — the formatter classes require PHP 8.0+ union types). Verified compatible with PHP 8.4
- `Formatters\LoverrcaseFormatter` renamed to `Formatters\LowercaseFormatter` (typo fix). Only relevant if you referenced the class directly — the `'lowercase'` config key is unchanged. No BC alias was kept

### Fixed
- Nested relation tokens (e.g. `[chat:lastChannel:name]`) now resolve correctly when the relation method name differs from the related model's class name — previously silently resolved to an empty string whenever the two didn't match (e.g. a `lastChannel()` relation returning a `Channel` model)
- A model method that is actually a Laravel 9+ attribute accessor, not an Eloquent relation, is no longer mistaken for an unresolved relation and dropped — its value is now returned instead of an empty string
- `formatters` now match the token's formatter suffix exactly instead of by substring — a field whose name merely *contained* a configured formatter's key (e.g. field `fullname` with a formatter named `name`) no longer had that formatter silently applied to it
- A token blocked by `disable_model_tokens` / `strTokenBlacklist()` no longer prints debug output
- An unresolvable `formatters` entry now throws `InvalidArgumentException`, as already documented, instead of a fatal error
- Built-in `formatters` (`uppercase`, `lowercase`, `trim`, `clearHtml`) no longer treat the string `"0"` as empty and silently discard it
- A non-scalar resolved value (e.g. a JSON-cast array column read without a formatter) no longer crashes the final replace step or silently becomes the literal text `"Array"` — it now resolves to an empty string, same as any other unresolved token

### Security
- `urlLink` formatter now HTML-escapes the value before embedding it in the generated `<a href='...'>` tag — an unescaped quote in the source value could break out of the attribute and inject arbitrary HTML
- A token naming a relation (e.g. `[order:manager:email]`) resolved with `can_traverse_relations` disabled no longer leaks the related model's full attribute set as JSON — it now resolves to an empty string, as the config option intends

## [2.7.0] - 2026-05-27

### Added
- Funding links (Monobank, Ko-fi, USDT TRC20) in README — no functional change

## [2.6.0] - 2026-05-02

### Changed
- Added Laravel 13 support

## [2.5.0] - 2026-03-24

### Changed
- Built-in formatters (`uppercase`, `lowercase`, `clearHtml`) moved to dedicated classes — no change to how you configure or use `formatters` in `config/str-tokens.php`

## [2.4.0] - 2025-09-24

### Added
- `formatters` config option — append `:formatterName` to any token (e.g. `[user:name:uppercase]`) to post-process its resolved value. Built-in: `trim`, `uppercase`, `lowercase`, `urlLink`, `clearHtml`; custom formatters can be any callable, invokable class (with a `handle()` method), or global function name

## [2.3.0] - 2025-03-05

### Changed
- Added Laravel 12 support

## [2.2.0] - 2025-03-04

### Changed
- Restored Laravel 9 support (dropped by mistake in 2.0)

## [2.1.0] - 2025-03-04

### Fixed
- Calling `setEntities()` now clears a previously set `setEntity()` — the two are not meant to be combined; if both end up set anyway, `setEntity()` still takes priority

## [2.0] - 2024-03-20

### Added
- Laravel 11 support
- A custom `strToken*()` method on a model is now recognized for a plain top-level token too (e.g. `[order:total]` calling `strTokenTotal()`) — previously this only worked for tokens with a `:`-separated sub-part

### Changed
- Minimum supported Laravel version raised to 10 (dropped 5.6–9 support)

## [1.6.0] - 2023-06-15

### Changed
- Added Laravel 10 support

## [1.5.0] - 2023-03-08

### Added
- `token_split_character` config — customize the `:` separator used inside a token
- `token_match_pattern` config — customize the token regex entirely (e.g. switch from `[type:name]` to a different bracket/delimiter style)
- `can_traverse_relations` config — disable automatic traversal into Eloquent relations for nested tokens
- `disable_model_tokens` config, plus `strTokenWhitelist()` / `strTokenBlacklist()` methods on a model — restrict which fields/tokens a model exposes

## [1.4.0] - 2022-02-12

### Changed
- Added Laravel 9 support

## [1.3.0] - 2020-11-09

### Changed
- Added Laravel 8 support

## [1.2.2] - 2020-04-16

### Fixed
- `[config:...]` and `[var:...]` tokens no longer return an empty string for numeric/boolean values — only non-scalar values (arrays, objects) are rejected now

## [1.2.0] - 2020-03-03

### Changed
- Added Laravel 7 support

## [1.1.0] - 2020-02-25

### Added
- `setVar()` / `setVars()` — arbitrary key-value tokens not tied to any Eloquent model, e.g. `[var:myKey]`

## [1.0.0] - 2019-09-12

### Added
- Initial stable release: `[type:field]` token resolution against Eloquent models, `setEntity()` / `setEntities()`, relation traversal, custom `strToken*()` methods on models, `[date:...]` and `[config:...]` tokens, Laravel 6 support

## [0.5.1] - 2019-05-09

### Changed
- Added Laravel 5.8 support

## [0.4.0] - 2019-02-10

### Added
- Support for resolving tokens against multiple entities at once (`setEntities()`)

## [0.2.1] - 2018-10-28

### Added
- `many` relations resolve using the first related model
- Taxonomy relation support (`tx` prefix) for [fomvasss/laravel-taxonomy](https://github.com/fomvasss/laravel-taxonomy)

## [0.0.1] - 2018-10-27

### Added
- Initial release
