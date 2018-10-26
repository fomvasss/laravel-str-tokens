<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 26.10.18
 * Time: 03:21
 */

namespace Fomvasss\LaravelStrTokens;

use App\Models\MetaTag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
 
class StrTokenGenerator
{
    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var null
     */
    protected $date = null;

    /**
     * @var null
     */
    protected $entity = null;

    /**
     * @var bool
     */
    protected $clearEmptyTokens = true;

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
     * @return StrTokenGenerator
     */
    public function setEntity(Model $entity): self
    {
        $this->entity = $entity;

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
                $replacements += $this->variablesTokens($attributes);

            } elseif ($this->entity && strtolower($key) === snake_case(class_basename($this->entity))) {
                $replacements += $this->eloquentModelTokens($attributes, $key);
            }

            if ($this->clearEmptyTokens) {
                $replacements += array_fill_keys($attributes, '');
            }
        }

        $attributes = array_keys($replacements);
        $values = array_values($replacements);

        return str_replace($attributes, $values, $this->text);
    }

    /**
     * Taken from the best CMS - Drupal :)
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
        preg_match_all('/
            \\[             # [ - pattern start
            ([^\\s\\[\\]:]*)  # match $type not containing whitespace : [ or ]
            :              # : - separator
            ([^\\[\\]]*)     # match $name not containing [ or ]
            \\]             # ] - pattern end
            /x', $text, $matches);
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
    protected function eloquentModelTokens(array $tokens, string $type): array
    {
        $replacements = [];

        foreach ($tokens as $key => $original) {
            $function = explode(':', $key)[0];
            $strTokenMethod = camel_case('str_token_'.$function);

            // is token generate method
            if (method_exists($this->entity, $strTokenMethod)) {

                $replacements[$original] = $this->entity->{$strTokenMethod}($this->entity, ...explode(':', $key));
                // is relation function
            } elseif (method_exists($this->entity, $function)) {

                $newOriginal = str_replace("$type:", '', $original);

                if ($this->entity->{$function} instanceof Model) {
                    $tm = new self();

                    $replacements[$original] = $tm->setText($newOriginal)->setEntity($this->entity->{$function})->replace();
                }
                // is field model
            } else {
                // TODO: check available fields
                $replacements[$original] = $this->entity->{$key};
            }
        }

        return $replacements;
    }

    /**
     * TODO: require https://github.com/fomvasss/laravel-variables
     *
     * @param $tokens
     * @return array
     */
    protected function variablesTokens(array $tokens): array
    {
        $replacements = [];

        foreach ($tokens as $name => $original) {
            $replacements[$original] = var_get($name);
        }

        return $replacements;
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function configTokens(array $tokens): array
    {
        $replacements = [];

        foreach ($tokens as $name => $original) {
            $res = config($name, '');
            $replacements[$original] = is_string($res) ? $res : '';
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
                $format = config('str-tokens.date.formats.'.$name, 'D, m/d/Y - H:i');
                $replacements[$original] = $this->date->format($format);
            }
        }

        return $replacements;
    }
}