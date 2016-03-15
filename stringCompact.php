<?php

/**
 * Compact a string to a maximum length
 *
 * @access public
 * @param string $str String to compact
 * @param integer $length Length to trim at
 * @return string Compact string; otherwise, original string
 */
function stringCompact($str, $length = 0)
{
    // mb_strwidth is better than using mb_strlen. See PHP docs for more details
    if ($length === 0 || mb_strwidth($str) <= $length) {
        return $str;
    }

    return mb_strimwidth($str, 0, $length, '...');
}
