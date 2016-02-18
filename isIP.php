<?php

/**
 * Validate an IP address
 * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Input.php
 *
 * @param string $ip IP address to validated
 * @param string $type IP protocol: 'ipv4' or 'ipv6'
 * @return bool True, is a valid IP address; otherwise, false
 */
function isIP($ip, $type = null)
{
    $ip = strtolower($type);
    switch ($ip) {
        case 'ipv4':
            $type = FILTER_FLAG_IPV4;
            break;

        case 'ipv6':
            $type = FILTER_FLAG_IPV6;
            break;

        default:
            $type = null;
            break;
    }

    return (bool) filter_var($ip, FILTER_VALIDATE_IP, $type);
}
