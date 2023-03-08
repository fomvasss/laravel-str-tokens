# Laravel Str Tokens

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-str-tokens)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-str-tokens)

With this package you can manage & generate strings with tokens/shortcodes, it seems like CMS Drupal.

----------

## Installation

Run from the command line:

```bash
composer require fomvasss/laravel-str-tokens
```

To publish the configs, run the following command:

```
php artisan vendor:publish --provider="Fomvasss\LaravelStrTokens\ServiceProvider"
```

Configuration file will be publish to `config/str-tokens.php`


## Configuration 
The configuration fill will allow you to control how tokens are parsed using `token_match_pattern` and `token_split_character`

You can decide if a token can traverse eloquent model relationships using `can_traverse_relations`

You can globally limit what model fields are allowed as tokens using `disable_model_tokens`

You can also limit what tokens are exposed via individual models by creating a `strTokenWhitelist` or `strTokenBlacklist` function that returns an array of valid patterns

## Usage

```php
$str = StrToken::setText('
            Example str with tokens for article: "[article:title] ([article:id])",
            Article created at date: [article:created_at],
            Author: [article:user:name]([article:user:id]).
            Article status: [article:txArticleStatus:name],
            Article root category: [article:txArticleCategories:root:name],
            User: [article:user:email], [article:user:city:country:title], [article:user:city:title].
            Generated token at: [config:app.name], [date:raw]
            [article:test:Hello]!!!
            Length: [var:length];
            Width: [var:width];
            Price: [var:price]
        ')
    ->setDate(\Carbon\Carbon::tomorrow())
    ->setEntity(\App\Model\Article::findOrFail(13))
    ->setVars(['length' => '2.2 m.', 'width' => '3.35 m.'])
    ->setVar('price', '$13')
    ->replace();

```

Given result:

```text
 Example str with tokens for article: "Test article title(23)",
 Article created at date: 15.07.2018,
 Author: Taylor Otwell(1),
 Article status: published,
 Article root category: Programming,
 User: taylorotwell@gmail.com, AR, Little Rock.
 Generated token at: Laravel, 2018-10-27 00:00:00
 TEST TOKEN:Hello!!! 
 Length: 2.2 m.;
 Width: 3.35 m.;
 Price: $13
```

You can use method `setEntities()` for set many Eloquent models, for example:
```php
<?php 
$user1 = User::find(1);
$user2 = User::find(2);
$article = Article::first();

$str = StrToken::setText('
		User1: [user1:name] / [user1:email]
		User2: [user2:name] / [user2:email]
		Article: "[firstArticle:title]"
	')->setEntities([
        'user1' => $user1,
        'user2' => $user2,
        'firstArticle' => $article,
    ])->replace();
	
	/*
	User: Taylor Otwell / taylorotwell@gmail.com
	User: Vasyl Fomin / fomvasss@gmail.com
	Article: "Laravel is awesome framework"
	*/
```

#### Defining custom tokens in Eloquent models

In your models you can create own methods for generate tokens.

The names of these methods must begin with `strToken`.

In next example, we create custom methods: `strTokenTest()`, `strTokenCreatedAt()`

And now we can use next token in string: 
```
This is [article:test], created at: [article:creted_at]
```
And result:

```
This is "TEST TOKEN", created at: 23.11.2018
```
_Example `Article` Eloquent model:_

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Fomvasss\Taxonomy\Models\Traits\HasTaxonomies;

class Article extends Model
{
    use HasTaxonomies;
    
    //...
    
    public function strTokenTest($entity, $method, $attr): string
    {
        // $entity - this article
        // $method - "test"
        // $attr - additional args
        return 'TEST TOKEN:' . $attr;
    }
    
    public function strTokenCreatedAt(): string
    {
        return $this->created_at->format('d.m.Y');	
    }
    
    // For package https://github.com/fomvasss/laravel-simple-taxonomy
    public function txArticleStatus()
    {
        return $this->term('status', 'system_name')
            ->where('vocabulary', 'post_statuses');
    }
}
```
_Example `Term` model:_

```php
<?php

namespace App\Models\Taxonomies;

use App\Article;

class Term extends \Fomvasss\Taxonomy\Models\Term
{
    public function articles()
    {
        return $this->morphedByMany(Article::class, 'termable');
    }

	/**
 	* Method for generate next example token for article model:
 	* [article:txArticleCategories:root:name]
	*	 
	* @param $entity
	* @param $r
	* @param $param
	* @return mixed
 	*/
    public function strTokenRoot($entity, $r, $param)
    {
        if ($root = $entity->ancestors->first()) {
            return $root->{$param};
        }

        return $entity->{$param};
    }
}
```


#### Use in blade template

```
@php(\StrToken::setEntity($article)->setDate($article->created_at))
@php(\StrToken::setText('[article:title] - [date:short]'))
<h3>{!! \StrToken::replace() !!}</h3>
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Links

* [laravel-simple-taxonomy](https://github.com/fomvasss/laravel-simple-taxonomy)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
