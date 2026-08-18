<?php

declare(strict_types=1);

namespace Fomvasss\LaravelStrTokens;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StrTokenGenerator
{
    /** @var \Illuminate\Foundation\Application The Laravel application instance. */
    protected $app;

    /** @var mixed The Laravel application configs. */
    protected $config;

    protected string $text = '';

    protected ?Carbon $date = null;

    protected ?Model $entity = null;

    // Коли true — replace() матчить $this->entity на будь-який $key групи токенів, а не лише
    // на snake(class_basename($entity)). Виставляється лише внутрішньо, для рекурсивного виклику
    // після traversal реального relation (eloquentModelTokens()), де $key — ім'я relation-методу,
    // а не назва класу моделі, на яку він вказує (напр. lastChannel() -> Channel) — без цього
    // прапорця такий вкладений токен мовчки резолвився б у порожній рядок
    protected bool $entityMatchAnyKey = false;

    /** @var array<string, Model> */
    protected array $entities = [];

    /** @var array<string, mixed> */
    protected array $vars = [];

    protected bool $clearEmptyTokens = true;

    /**
     * StrTokenGenerator constructor.
     */
    public function __construct($app = null)
    {
        if (!$app) {
            $app = app();   //Fallback when $app is not given
        }
        $this->app = $app;

        $this->config = $this->app['config'];
    }

    /**
     * @param string $text
     * @return StrTokenGenerator
     */
    public function setText(string $text = ''): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param Carbon $date
     * @return StrTokenGenerator
     */
    public function setDate(Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param Model $entity
     * @param bool $matchAnyKey Внутрішній прапорець для рекурсії по relation — див. коментар біля $entityMatchAnyKey
     * @return StrTokenGenerator
     */
    public function setEntity(Model $entity, bool $matchAnyKey = false): self
    {
        $this->entity = $entity;
        $this->entityMatchAnyKey = $matchAnyKey;

        return $this;
    }

    /**
     * @param array<string, Model> $entities
     * @return \Fomvasss\LaravelStrTokens\StrTokenGenerator
     * @throws InvalidArgumentException
     */
    public function setEntities(array $entities): self
    {
        foreach ($entities as $key => $entity) {
            $this->ensureValidEntity($entity);
        }

        if (count($entities)) {
            $this->entity = null;
        }

        $this->entities = $entities;

        return $this;
    }

    /**
     * @param array $vars
     * @return $this
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setVar(string $key, $value): self
    {
        $this->vars[$key] = $value;

        return $this;
    }

    /**
     * @return StrTokenGenerator
     */
    public function doNotClearEmptyTokens(): self
    {
        $this->clearEmptyTokens = false;

        return $this;
    }

    /**
     * @return StrTokenGenerator
     */
    public function clearEmptyTokens(): self
    {
        $this->clearEmptyTokens = true;

        return $this;
    }

    /**
     * @return string
     */
    public function replace(): string
    {
        $groupTokens = $this->tokenScan($this->text);
        $replacements = [];

        foreach ($groupTokens as $key => $attributes) {

            if ($key === 'date') {
                $replacements += $this->dateTokens($attributes);

            } elseif ($key === 'config') {
                $replacements += $this->configTokens($attributes);

            } elseif ($key === 'var') {
                $replacements += $this->varTokens($attributes);

            } elseif ($this->entity && ($this->entityMatchAnyKey || strtolower($key) === Str::snake(class_basename($this->entity)))) {
                $replacements += $this->eloquentModelTokens($this->entity, $attributes, $key);

            // For related taxonomy: https://github.com/fomvasss/laravel-simple-taxonomy
            // and you set preffix in your relation methods - "tx"
            } elseif ($this->entity && substr($key, 0, 2) === 'tx') {
                $replacements += $this->eloquentModelTokens($this->entity, $attributes, $key);

            } elseif (in_array($key, array_keys($this->entities))) {
                $eloquentModel = $this->entities[$key];
                $replacements += $this->eloquentModelTokens($eloquentModel, $attributes, $key);
            } elseif ($this->entity && method_exists($this->entity, $strTokenMethod = Str::camel('str_token_'.$key))) {
                $replacements += $this->eloquentModelTokens($this->entity, $attributes, $key);
            }

            if ($this->clearEmptyTokens) {
                $replacements += array_fill_keys($attributes, '');
            }
        }

        $attributes = array_keys($replacements);
        // Значення може бути non-scalar (JSON-cast колонка -> array, або Model, якщо
        // can_traverse_relations=false і токен усе одно вказує на ім'я relation-методу) —
        // без нормалізації str_replace() мовчки перетворив би такий елемент на літерал
        // "Array" (PHP array-to-string coercion) або впав би TypeError. Stringable-об'єкти
        // (напр. Carbon для [date:raw]) навмисно лишаються як є — str_replace() сам викличе
        // їхній __toString()
        $values = array_map(function ($value) {
            if ($value === null || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                return $value;
            }

            return '';
        }, array_values($replacements));

        return str_replace($attributes, $values, $this->text);
    }

    /**
     * Token scan with CMS Drupal :)
     * https://api.drupal.org/api/drupal/includes%21token.inc/function/token_scan/7.x
     * preg_match_all('/\[([^\]:]*):([^\]]*)\]/', $tokenStr, $matches);
     *
     * @param $text
     * @return array
     */
    private function tokenScan(string $text): array
    {

        // Matches tokens with the following pattern: [$type:$name]
        // $type and $name may not contain  [ ] characters.
        // $type may not contain : or whitespace characters, but $name may.
        preg_match_all($this->config->get('str-tokens.token_match_pattern','/
            \\[             # [ - pattern start
            ([^\\s\\[\\]:]*)  # match $type not containing whitespace : [ or ]
            :              # : - separator
            ([^\\[\\]]*)     # match $name not containing [ or ]
            \\]             # ] - pattern end
            /x'), $text, $matches);
        $types = $matches[1];
        $tokens = $matches[2];

        // Iterate through the matches, building an associative array containing
        // $tokens grouped by $types, pointing to the version of the token found in
        // the source text. For example, $results['node']['title'] = '[node:title]';
        $results = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $results[$types[$i]][$tokens[$i]] = $matches[0][$i];
        }

        return $results;
    }

    /**
     * @param array $tokens
     * @param string $type
     * @return array
     */
    protected function eloquentModelTokens(Model $eloquentModel, array $tokens, string $type): array
    {
        $replacements = [];

        $disable = array_merge(
            $this->config->get('str-tokens.disable_model_tokens', []),
            method_exists($eloquentModel, 'strTokenBlacklist') ? $eloquentModel->strTokenBlacklist() : []
        );
        $whitelist = method_exists($eloquentModel, 'strTokenWhitelist') ? $eloquentModel->strTokenWhitelist() : false;
        $delim = $this->config->get('str-tokens.token_split_character', ':');
        $canTraverseRelations = $this->config->get('str-tokens.can_traverse_relations', true);

        foreach ($tokens as $key => $original) {
            if(!empty($whitelist) && !Str::is($whitelist,$key)){ continue; }
            if (Str::is($disable, $key)) { continue; }
            $function = explode($delim, $key)[0];
            $strTokenMethod = Str::camel('str_token_'.$function);

            // Exists token generate method (defined user)
            if (method_exists($eloquentModel, $strTokenMethod)) {

                $replacements[$original] = $eloquentModel->{$strTokenMethod}($eloquentModel, ...explode($delim, $key));

            // Exists relation function (defined user)
            } elseif ($canTraverseRelations && method_exists($eloquentModel, $function)) {

                $newOriginal = str_replace($type.$delim, '', $original);
                $value = $eloquentModel->{$function};

                if ($value instanceof Model) {
                    $tm = new static();

                    // matchAnyKey: $newOriginal тут завжди рівно один токен (напр. "[lastChannel:name]"),
                    // вирізаний із $key ($function === "lastChannel") — а не назва класу $value ("Channel").
                    // Матчити по назві класу тут безглуздо, бо relation-метод майже завжди зветься не
                    // так, як клас, на який він вказує
                    $replacements[$original] = $tm->setText($newOriginal)->setEntity($value, true)->replace();
                } elseif ($value instanceof Collection && ($firstRelatedEntity = $value->first())) {
                    $tm = new static();

                    $replacements[$original] = $tm->setText($newOriginal)->setEntity($firstRelatedEntity, true)->replace();
                } else {
                    // $function exists as a method but isn't actually a relation returning a Model/Collection —
                    // typically a Laravel 9+ attribute accessor (protected function name(): Attribute) sharing
                    // its method name with the token key, which method_exists() can't tell apart from a real
                    // relation method. Fall back to the plain-value read below instead of silently dropping
                    // the token (previously: neither branch matched, $replacements[$original] was never set,
                    // and the token quietly resolved to '' via clearEmptyTokens).
                    $replacements[$original] = $value;
                }
            // Is field model
            } else {
                // TODO: make and check available model fields
                //dd($eloquentModel->{$key});
                $field = explode($delim, $key)[0];
                $value = $eloquentModel->{$field};

                // Сюди потрапляє і токен, що називає relation, коли can_traverse_relations=false
                // (Eloquent сам резолвить relation через магічний __get незалежно від прапорця) —
                // без guard'а Model/Collection просочується у фінальний replace() і серіалізується
                // в JSON через Model::__toString(), замість очікуваного порожнього рядка
                $replacements[$original] = ($value instanceof Model || $value instanceof Collection) ? '' : $value;
            }

            // Формат — рівно один формате: [type:field:formatterName] ("All formatters added
            // after last symbol :" в README). Порівняння точне ($tail === $formatterKey), не
            // Str::contains — інакше формате міг спрацювати на полі, чия назва просто МІСТИТЬ
            // ім'я формате підрядком (напр. поле "fullname" і formatter "name": "fullname"
            // містить "name" підрядком), або кілька схожих формате спрацьовували б одночасно
            $tail = strrchr($key, ':');
            if ($tail !== false) {
                $tail = substr($tail, 1);
                $formatterFunc = $this->config->get("str-tokens.formatters.{$tail}");
                if ($formatterFunc !== null) {
                    $replacements[$original] = $this->callFormatter($formatterFunc, $replacements[$original]);
                }
            }
        }

        return $replacements;
    }

    /**
     * @param $formatter
     * @param $value
     * @return string
     * @throws \Exception
     */
    protected function callFormatter($formatter, $value)
    {
        // Значення поля моделі може бути int/bool/float (звичайна нетекстова колонка) —
        // раніше (без strict_types) PHP мовчки коерсив це в string на вході в handle(string|null).
        // Явний cast зберігає ту саму поведінку тепер, коли автокоерсія вимкнена
        if ($value !== null && !is_string($value) && is_scalar($value)) {
            $value = (string) $value;
        }

        if (is_callable($formatter)) {
            return $formatter($value);
        }

        if (is_string($formatter) && class_exists($formatter)) {
            $instance = app($formatter);
            if (method_exists($instance, 'handle')) {
                return $instance->handle($value);
            }
        }

        if (is_string($formatter) && function_exists($formatter)) {
            return $formatter($value);
        }

        throw new InvalidArgumentException("Formatter [$formatter] not supported.");
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function configTokens(array $tokens): array
    {
        $replacements = [];

        $disable = $this->config->get('str-tokens.disable_configs', []);

        foreach ($tokens as $name => $original) {
            if (! Str::is($disable, $name)) {
                $res = $this->config->get($name, '');
                $replacements[$original] = is_scalar($res) ? $res : '';
            }
        }

        return $replacements;
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function varTokens(array $tokens): array
    {
        $replacements = [];

        foreach ($tokens as $name => $original) {
            $res = $this->vars[$name] ?? '';
            $replacements[$original] = is_scalar($res) ? $res : '';
        }

        return $replacements;
    }

    /**
     * @param $tokens
     * @return array
     */
    protected function dateTokens(array $tokens):array
    {
        $this->date = $this->date ?: Carbon::now();
        $replacements = [];

        foreach ($tokens as $name => $original) {
            if ($name === 'raw') {
                $replacements[$original] = $this->date;
            } else {
                $format = $this->config->get('str-tokens.date.formats.'.$name, 'D, m/d/Y - H:i');
                $replacements[$original] = $this->date->format($format);
            }
        }

        return $replacements;
    }

    /**
     * @param mixed $entity
     * @throws InvalidArgumentException
     */
    protected function ensureValidEntity($entity): void
    {
        if (! $entity instanceof Model) {
            throw new InvalidArgumentException("StrToken Entity must by instance of '" . Model::class . "'. Current instance of '" . gettype($entity) . "'");
        }
    }
}


