<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

if (!function_exists('carbon')) {
    /**
     * Carbon helper function.
     *
     * @see https://github.com/laravel/framework/pull/21660#issuecomment-338359149
     *
     * @param mixed ...$params
     * @return Carbon
     */
    function carbon(...$params)
    {
        if (!$params) {
            return now();
        }

        if ($params[0] instanceof DateTime) {
            return Carbon::instance($params[0]);
        }

        if (is_numeric($params[0]) && ((string)(int)$params[0] === (string)$params[0])) {
            return Carbon::createFromTimestamp(...$params);
        }

        return Carbon::parse(...$params);
    }
}

if (!function_exists('clamp')) {
    /**
     * Clamps $current number between $min and $max.
     *
     * (tfw no generics)
     *
     * @param int|float $current
     * @param int|float $min
     * @param int|float $max
     * @return int|float
     */
    function clamp($current, $min, $max) {
        return max($min, min($max, $current));
    }
}

if (!function_exists('generate_sentence_from_array')) {
    /**
     * Generates a string with conjunction from an array of strings.
     *
     * @param array $stringParts
     * @param string $delimiter
     * @param string $lastDelimiter
     * @return string
     */
    function generate_sentence_from_array(
        array $stringParts,
        string $delimiter = ', ',
        string $lastDelimiter = ' and '
    ): string {
        if(count($stringParts) > 2)
        {
            $lastDelimiter = ', and ';
        }
        return Str::replaceLast($delimiter, $lastDelimiter, implode($delimiter, $stringParts));
    }
}

if (!function_exists('display_number_format')) {
    /**
     * Generates a string with conjunction from an array of strings.
     *
     * @param array $stringParts
     * @param string $delimiter
     * @param string $lastDelimiter
     * @return string
     */
    function display_number_format(float $number, int $decimals = 0): string
    {
        if(strpos($number, '.'))
        {
            $decimals = min(strpos(strrev($number), '.'), 3);
        }

        return number_format($number, $decimals);
    }
}

# 

if (!function_exists('dominion_attr_display')) {
    /**
     * Returns a string suitable for display with prefix removed.
     *
     * @param string $attribute
     * @param float $value
     * @return string
     */
    function dominion_attr_display(string $attribute, float $value = 1): string {
        $pluralAttributeDisplay = [
            'prestige' => 'prestige',
            'morale' => 'morale',
            'spy_strength' => 'percent spy strength',
            'wizard_strength' => 'percent wizard strength',
            'resource_gold' => 'gold',
            'resource_food' => 'food',
            'resource_lumber' => 'lumber',
            'resource_mana' => 'mana',
            'resource_ore' => 'ore',
            'xp' => 'xp',
            'land_water' => 'water',
        ];

        if (isset($pluralAttributeDisplay[$attribute])) {
            return $pluralAttributeDisplay[$attribute];
        } else {
            if (strpos($attribute, '_') !== false) {
                $stringParts = explode('_', $attribute);
                array_shift($stringParts);
                return Str::plural(Str::singular(implode(' ', $stringParts)), $value);
            } else {
                return Str::plural(Str::singular($attribute), $value);
            }
        }
    }
}

if (!function_exists('random_chance')) {
    $mockRandomChance = false;
    /**
     * Returns whether a random chance check succeeds.
     *
     * Used for the very few RNG checks in OpenDominion.
     *
     * @param float $chance Floating-point number between 0.0 and 1.0, representing 0% and 100%, respectively
     * @return bool
     * @throws Exception
     */
    function random_chance(float $chance): bool
    {
        #global $mockRandomChance;
        #if ($mockRandomChance === true) {
        #    return false;
        #}

        return ((random_int(0, mt_getrandmax()) / mt_getrandmax()) <= $chance);
    }
}

if (!function_exists('number_string')) {
    /**
     * Generates a string from a number with number_format, and optionally an
     * explicit + sign prefix.
     *
     * @param int|float $number
     * @param int $numDecimals
     * @param bool $explicitPlusSign
     * @return string
     */
    function number_string($number, int $numDecimals = 0, bool $explicitPlusSign = false): string {
        $string = number_format($number, $numDecimals);

        if ($explicitPlusSign && $number > 0) {
            $string = "+{$string}";
        }

        return $string;
    }
}

if(!function_exists('ldump'))
{
    /**
     * Dumps but only if running locally.
     *
     * @param string $word
     * @return string
     */

     function ldump($input = null)
    {
        if(env('APP_ENV') == 'local')
        {
            dump($input);
        }
    }
}


if (!function_exists('ldd')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function ldd(...$inputs)
    {
        if (env('APP_ENV') === 'local') {
            foreach ($inputs as $input) {
                dump($input);
            }

            die(1);
        }
    }
}

if (!function_exists('isLocal')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function isLocal()
    {
        return env('APP_ENV') === 'local';
    }
}

if(!function_exists('negative'))
{
    /**
     * Always reeturns the value as a negative.
     *
     * @param string $word
     * @return string
     */

     function negative(float $value): float
     {
         return -abs($value);
     }
     
}


if (!function_exists('xtLog')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function xtLog($string, $type = 'info'): void
    {
        if(config('game.extended_logging'))
        {

            if($type == 'error')
            {
                Log::error($string);
            }
            elseif($type == 'warning')
            {
                Log::warning($string);
            }
            elseif($type == 'debug')
            {
                Log::debug($string);
            }
            else
            {
                Log::info($string);
            }
        }

        if(config('game.extended_logging_with_dump'))
        {
            dump($string);
        }
    }
}


if (!function_exists('floorInt')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function floorInt($number): int
    {
        return (int)floor($number);
    }
}


if (!function_exists('ceilInt')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function ceilInt($number): int
    {
        return (int)ceil($number);
    }
}


if (!function_exists('roundInt')) {
    /**
     * Dumps the given variables but only if running locally.
     */
    function roundInt($number): int
    {
        return (int)round($number);
    }
}