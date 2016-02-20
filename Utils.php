<?php

namespace Utils;

class Utils
{
    /**
     * Generate a globally unique identifier (GUID)
     *
     * @access public
     * @return string Generated globally unique identifier (GUID)
     */
    public static function guid()
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

    /**
     * Check if the request was an ajax request
     *
     * @access public
     * @return boolean True, the request was an ajax request; otherwise, false
     */
    public static function isAjaxRequest()
    {
        $request = $_SERVER['HTTP_X_REQUESTED_WITH'];

        return !empty($request) && strtolower($request) === 'xmlhttprequest';
    }

    /**
     * Check if accessed from the the command-line interface (CLI)
     * Idea by PHP, URL: http://php.net/manual/en/features.commandline.php
     *
     * @access public
     * @return boolean True, using the command-line interface (CLI); otherwise, false
     */
    public static function isCLI()
    {
        return PHP_SAPI === 'cli' || defined('STDIN');
    }

    /**
     * Check if a valid e-mail address. Compliant with the RFC 822 specification
     *
     * @access public
     * @param string $email E-mail address to check
     * @return boolean True, is a valid e-mail address; otherwise, false
     */
    public static function isEmailAddress($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if behind an encrypted connection
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Common.php
     *
     * @access public
     * @return boolean True, using an encrypted connection; otherwise, false
     */
    public static function isHTTPS()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Validate an IP address
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Input.php
     *
     * @access public
     * @param string $ip IP address to validated
     * @param string $type IP protocol: 'ipv4' or 'ipv6'
     * @return boolean True, is a valid IP address; otherwise, false
     */
    public static function isIPAddress($ip, $type = null)
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

    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Common.php
     *
     * @access public
     * @param string Version number to check
     * @return boolean True, the supplied version is greater or equal to the current PHP version
     */
    public static function isPHP($version)
    {
        // Cast as a string
        $version = (string) $version;

        return version_compare(PHP_VERSION, $version, '>=');
    }

    /**
     * Check if a valid url. Compliant with the RFC 2396 specification
     *
     * @access public
     * @param string $url URL to check
     * @return boolean True, is a valid url; otherwise, false
     */
    public static function isURL($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Check if a string is UTF-8 compliant
     *
     * @access public
     * @param string $value String value to check
     * @return boolean True the value is UTF-8 compliant; otherwise false
     */
    public static function isUTF8($value)
    {
        return mb_check_encoding($value, 'UTF-8');
    }

    /**
     * Get the request body as a JSON object
     *
     * @access public
     * @param mixed $default Default value if an error occurs. Default is null
     * @return object|null JSON object; otherwise, $default on error
     */
    public static function requestJSON($default = null)
    {
        $contents = file_get_contents('php://input');

        return $contents === false ? $default : json_decode($contents);
    }

    /**
     * Get the request method
     *
     * @access public
     * @param boolean $toUpperCase Convert the method to upper-case if true; otherwise, lower-case if false. Default is true
     * @return string Formatted request method
     */
    public static function requestMethod($toUpperCase = true)
    {
        $request = $_SERVER['REQUEST_METHOD'];

        return $toUpperCase === false ? strtolower($request) : strtoupper($request);
    }

    /**
     * Compact a string to a maximum length
     *
     * @access public
     * @param string $value String to compact
     * @param integer $length Length to trim at
     * @return string Compact string; otherwise, original string
     */
    public static function strCompact($value, $length = 0)
    {
        // Better than using mb_strlen
        if (mb_strwidth($value) <= $length) {
            return $value;
        }

        return mb_strimwidth($value, 0, $length, '...');
    }

    /**
     * Retrieve the length of a given string
     *
     * @access public
     * @param string $value String to check
     * @return integer String value length
     */
    public static function strLen($value)
    {
        return mb_strlen($value);
    }

    /**
     * Convert a snake case string to camel case string
     * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
     *
     * @access public
     * @param string $value Snake case string
     * @return string String as camel case
     */
    public static function strToCamelCase($value)
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        $value =  str_replace(' ', '', $value);

        return lcfirst($value);
    }

    /**
     * Convert a string to lower-case
     *
     * @access public
     * @param string $value String to change to lower-case
     * @return string Lower-case string
     */
    public static function strToLower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert a string to title-case
     *
     * @access public
     * @param string $value String to change to title-case
     * @return string Title-case string
     */
    public static function strToTitle($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert a string to upper-case
     *
     * @access public
     * @param string $value String to change to upper-case
     * @return string Upper-case string
     */
    public static function strToUpper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert a string to a snake case string
     * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
     *
     * @access public
     * @param string $value Camel case string
     * @param string $delimiter Delimiter to use. Default '_'
     * @return string String as snake case
     */
    public static function strToSnakeCase($value, $delimiter = '_')
    {
        // Pre-append the delimiter before an upper-case character
        $value = preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value);

        return mb_strtolower($value);
    }
}
