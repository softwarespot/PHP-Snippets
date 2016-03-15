<?php

/**
 * Validate an IP address
 * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Input.php
 *
 * @access public
 * @param string $ip IP address to validated
 * @param string $type IP protocol: 'ipv4' or 'ipv6'. Default is 'ipv4'
 * @param boolean $exludePrivAndRes Exclude private and reserved ranges. Default it false
 * @return boolean True, is a valid IP address; otherwise, false
 */
function isIP($ip, $type = FILTER_FLAG_IPV4, $exludePrivAndRes = false)
{
    // Check if the value is falsy
    if (empty($ip)) {
        return false;
    }

    $type = strtolower($type);

    switch ($type) {
        case 'ipv4':
            $type = FILTER_FLAG_IPV4;
            break;

        case 'ipv6':
            $type = FILTER_FLAG_IPV6;
            break;

        default:
            $type = FILTER_FLAG_IPV4;
            break;
    }

    // Check the default value is boolean
    is_bool($exludePrivAndRes) || $exludePrivAndRes = false;

    if ($exludePrivAndRes) {
        // Use bitwise OR when excluding the private and reserved address ranges
        $type |= FILTER_FLAG_NO_PRIV_RANGE;
        $type |= FILTER_FLAG_NO_RES_RANGE;
    }

    return (bool) filter_var($ip, FILTER_VALIDATE_IP, $type);
}
