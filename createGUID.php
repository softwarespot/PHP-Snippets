<?php

/**
 * Generate a globally unique identifier (GUID)
 *
 * @access public
 * @return string Generated globally unique identifier (GUID)
 */
function createGUID()
{
    // Cache whether there is a native function available
    static $_isNativeFn;

    // Use the native function if it exists
    if ($_isNativeFn || function_exists('com_create_guid')) {
        $_isNativeFn = true;

        return com_create_guid();
    }

    // Generate a random GUID
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
