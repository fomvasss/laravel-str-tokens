# Laravel Str Tokens

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-str-tokens)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-str-tokens)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-str-tokens.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-str-tokens)

With this package you can manage & generate strings with tokens, it seems like CMS Drupal.

----------

## Installation

Run from the command line:

```bash
composer require fomvasss/laravel-str-tokens
```

For publish the configs, run next on the command line:

```
php artisan vendor:publish --provider="Fomvasss\LaravelStrTokens\ServiceProvider"
```
- A configuration file will be publish to `config/str-tokens.php`


## Examples usage

### Use `StrToken` facade in your controllers

```php
<?php 

namespace App\Http\Controllers;

use Fomvasss\LaravelStrTokens\Facades\StrToken;
use Illuminate\Http\Request;

class HomeController extends Controller 
{
    
    public function store(Request $request)
    {
        $title = StrToken::setText($request->title . ' - [user:name], [date:date]')
            ->setEntity(Auth::user())
            ->replace();
        $article = \App\Model\Article::create([
            'user_id' => Auth::id(),
            'title' => $title,
            'body' => 'Hello world!'
        ]);

        //..
    }

    public function show($id)
    {
        $article = \App\Model\Article::findOrFail($id);
        
        $str = StrToken::setText('
                    Example str with tokens for article: "[article:title]([article:id])",
                    Article created at date: [article:created_at],
                    Author: [article:user:name]([article:user:id]).
                    Article first category: [article:txArticleCategories:name],
                    Article root category: [article:txArticleCategories:root:name],
                    Article status: [article:txArticleStatus:name],
                    User: [article:user:email], [article:user:city:country:title], [article:user:city:title].
                    Generated token at: [config:app.name], [date:raw]
                    [article:test:Hello]!!!
                    Length: [var:length];
                    Width: [var:width];
                    Price: [var:price]
                ')
            ->setDate(\Carbon\Carbon::tomorrow())
            ->setEntity($article)
            ->setVars(['length' => '2.2 m.', 'width' => '3.35 m.'])
            ->setVar('price', '$13')
            ->replace();
                
        print_r($str);
        /*
         Example str with tokens for article: "Test article title(23)",
         Article created at date: 15.07.2018,
         Author: Taylor Otwell(1),
         Article first category: Web-programming,
         Article root category: Programming,
         Article status: article_publish,
         User: taylorotwell@gmail.com, AR, Little Rock.
         Generated token at: Laravel, 2018-10-27 00:00:00
         TEST TOKEN:Hello!!! 
         Length: 2.2 m.;
         Width: 3.35 m.;
         Price: $13
         */        

        return view('stow', compact('article', 'str'));
    }
}
```

Also, you can use `setEntities()` method for set many models, for example:
```php
<?php 
$user1 = User::find(1);
$user2 = User::find(2);
$article = Article::first();

$str = StrToken::setText('
		User1: [user1:name]/[user1:email]
		User2: [user2:name]/[user2:email]
		Article: "[firstArticle:title]"
	')->setEntities([
        'user1' => $user1,
        'user2' => $user2,
        'firstArticle' => $article,
    ])->replace();
	
	/*
	User: Taylor Otwell/taylorotwell@gmail.com
	User: Vasyl Fomin/fomvasss@gmail.com
	Article: "Laravel is cool framework"
	*/
```

#### Defining your tokens in Eloquent models

In your models you can create own methods for generate tokens.
In next example, we create custom methods: `strTokenTest()`, `strTokenCreatedAt()`

And now we can use next token in string: 
```
This is [article:test], created at: [article:creted_at]
```
And result:

```
This is "TEST TOKEN", created at: 23.11.2018
```
__Article model:__

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
        // $attr - attributes
        return 'TEST TOKEN:' . $attr;
    }
    
    public function strTokenCreatedAt(): string
    {
        return $this->created_at->format('d.m.Y');	
    }
    
    // For package https://github.com/fomvasss/laravel-taxonomy
    public function txArticleCategories()
    {
        return $this->termsByVocabulary('product_categories');
    }
    
    // For package https://github.com/fomvasss/laravel-taxonomy
    public function txArticleStatus()
    {
        return $this->term('status', 'system_name')
            ->where('vocabulary', 'post_statuses');
    }
}
```
__Term model:__

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

* [laravel-taxonomy](https://github.com/fomvasss/laravel-taxonomy)
* [Use perfect package for url-aliases](https://github.com/fomvasss/laravel-url-aliases)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.