<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 07.11.18
 * Time: 23:22
 */

namespace Fomvasss\LaravelStrTokens;


class Helpers
{
    /**
     * @param $value
     * @return array
     */
    public static function arrayWrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return ! is_array($value) ? [$value] : $value;
    }

    /**
     * @param $pattern
     * @param $value
     * @return bool
     */
    public static function strIs($pattern, $value)
    {
        $patterns = static::arrayWrap($pattern);

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }
}