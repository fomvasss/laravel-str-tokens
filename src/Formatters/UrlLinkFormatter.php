<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens\Formatters;

class UrlLinkFormatter
{
    public function handle(string|null $str): string
    {
        // === null, не truthy-перевірка — рядок "0" є falsy в PHP і мовчки губився б
        if ($str === null) {
            return '';
        }

        // htmlspecialchars() — $str довільне поле моделі (може прийти з неконтрольованого
        // джерела: контакт, форма тощо). Без екранування лапка в значенні розриває href='...'
        // і дозволяє інʼєкцію довільного HTML/атрибута в цей самий тег
        $escaped = htmlspecialchars($str, ENT_QUOTES);

        return "<a href='{$escaped}'>{$escaped}</a>";
    }
}
