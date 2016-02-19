<?php

/**
 * Convert a camel case string to a snake case string
 * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
 *
 * @access public
 * @param string $value Camel case string
 * @param string $delimiter Delimiter to use. Default '_'
 * @return string String as snake case
 */
function toSnakeCase($value, $delimiter = '_')
{
    // Pre-append the delimiter before an upper-case character
    $value = preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value);

    return mb_strtolower($value);
}
