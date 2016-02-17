<?php

/**
 * Convert a snake case string to camel case string
 * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
 *
 * @access public
 * @param string $value Snake case string
 * @return string String as camel case
 */
function toCamelCase($value)
{
    $value = str_replace(['-', '_'], ' ', $value);
    $value = ucwords($value);
    $value =  str_replace(' ', '', $value);

    return lcfirst($value);
}
