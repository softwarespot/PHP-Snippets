<?php

/**
 * Compact a string to a maximum length
 *
 * @access public
 * @param string $value String to compact
 * @param integer $length Length to trim at
 * @return string Compact string; otherwise, original string
 */
function stringCompact($value, $length = 0)
{
    if ($length === 0 || mb_strlen($value) <= $length) {
        return $value;
    }

    return mb_strimwidth($value, 0, $length, '...');
}
