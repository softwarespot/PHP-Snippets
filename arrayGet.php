<?php

/**
 * Get a value from an array based on a particular key
 *
 * @access public
 * @param mixed $needle Key to search for
 * @param array $haystack Array to search in
 * @return mixed|null The value from the array; otherwise, null
 */
function arrayGet($needle, &$haystack)
{
    return array_key_exists($needle, $haystack) ? $haystack[$needle] : null;
}
