<?php

/**
 * Determines if the current version of PHP is equal to or greater than the supplied value
 * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Common.php
 *
 * @access public
 * @param string Version number to check
 * @return boolean True, the supplied version is greater or equal to the current PHP version
 */
function isPHP($version)
{
    // Cache the previous version numbers queried
    static $_isPHP;

    // Cast as a string
    $version = (string) $version;

    if (!isset($_isPHP[$version])) {
        $_isPHP[$version] = version_compare(PHP_VERSION, $version, '>=');
    }

    return $_isPHP[$version];
}
