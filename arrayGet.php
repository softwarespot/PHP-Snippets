<?php

/**
 * Get a value from an array based on a particular key
 *
 * @access public
 * @param mixed $needle Key to search for
 * @param array $haystack Array to search in
 * @param mixed $default Default value if not found. Default is null
 * @return mixed|null The value from the array; otherwise, $default on error
 */
function arrayGet($needle, &$haystack, $default = null)
{
    return array_key_exists($needle, $haystack) ? $haystack[$needle] : $default;
}
