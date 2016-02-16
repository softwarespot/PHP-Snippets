<?php

/**
 * Create a GUID
 *
 * @access public
 * @return string Random GUID
 */
function createGUID()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    }

    return sprintf(
        '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}
