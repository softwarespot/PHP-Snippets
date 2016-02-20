<?php

namespace Utils;

/**
 * A set of static utility functions
 *
 * Note: All string functions support UTF-8 string, unless Utils::defaultCharset is overridden
 * Style: The coding style for this utility class is PSR-2
 */
class Utils
{
    // Constants
    const IP_ADDRESS_V4 = 'ipv4';
    const IP_ADDRESS_V6 = 'ipv4';

    // Default charset
    protected static $defaultCharset = 'UTF-8';

    /**
     * Get a value from an array based on a particular key
     *
     * @access public
     * @param mixed $needle Key to search for
     * @param array $haystack Array to search in
     * @param mixed $default Default value if not found. Default is null
     * @return mixed|null The value from the array; otherwise, $default on error
     */
    public static function arrayGet($needle, &$haystack, $default = null)
    {
        // Using array_key_exists() denotes if the key actually exists
        return array_key_exists($needle, $haystack) ? $haystack[$needle] : $default;
    }

    /**
     * Get the client's IP address
     *
     * @access public
     * @return string Client's IP address; otherwise, null on error
     */
    public static function clientIPAddress()
    {
        // Maybe use this in the future: http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
        if (!empty($_SERVER['REMOTE_ADDR']) && Utils::isIPAddress($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
        }

        return null;
    }

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
     * Returns a HTML escaped variable
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Common.php
     *
     * @access public
     * @param mixed $value The input string or array of strings to be escaped
     * @param boolean $doubleEncode Set to true to escape twice. Default is false
     * @return mixed The escaped string or array of strings
     */
    public static function htmlEscape($value, $doubleEncode = false)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            // There is little performance difference between using $key => $value and array_keys()
            foreach (array_keys($value) as $key) {
                $value[$key] = html_escape($value[$key], $doubleEncode);
            }

            return $value;
        }

        return htmlspecialchars($value, ENT_QUOTES, self::$defaultCharset, $doubleEncode);
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
     * Check if a variable is a floating point value
     *
     * @access public
     * @param mixed $value Value to check
     * @return boolean True, the value is a floating point; otherwise, false
     */
    public static function isFloat($value) {
        $reIsFloat = "/(?:^-?(?!0{2,})\\d+\\.\\d+$)/";

        return (bool) preg_match($reIsFloat, (string) $value);
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
        if (!isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Check if a variable is an integer value
     *
     * @access public
     * @param mixed $value Value to check
     * @return boolean True, the value is an integer; otherwise, false
     */
    public static function isInteger($value) {
        $reIsInteger = "/(?:^-?(?!0+)\\d+$)/";

        return (bool) preg_match($reIsInteger, (string) $value);
    }

    /**
     * Validate an IP address
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Input.php
     *
     * @access public
     * @param string $ip IP address to validated
     * @param string $type IP protocol: Utils::IP_ADDRESS_V4 ('ipv4') or Utils::IP_ADDRESS_V6 ('ipv6'). Default is Utils::IP_ADDRESS_V4
     * @param boolean $exludePrivAndRes Exclude private and reserved ranges. Default it false
     * @return boolean True, is a valid IP address; otherwise, false
     */
    public static function isIPAddress($ip, $type = FILTER_FLAG_IPV4, $exludePrivAndRes = false)
    {
        $ip = strtolower($type);

        switch ($ip) {
            case self::IP_ADDRESS_V4:
                $type = FILTER_FLAG_IPV4;
                break;

            case self::IP_ADDRESS_V6:
                $type = FILTER_FLAG_IPV6;
                break;

            default:
                $type = FILTER_FLAG_IPV4;
                break;
        }

        // Ensure $exludePrivAndRes is false by default by explicitly checking the type and value
        if ($exludePrivAndRes === true) {
            // Bitwise OR
            $type |= FILTER_FLAG_NO_PRIV_RANGE;
            $type |= FILTER_FLAG_NO_RES_RANGE;
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
     * Wrapper for checking a variable is set and using the value if so
     *
     * @access public
     * @param mixed $value Value to check
     * @param mixed $default Default value to use if not set
     * @return mixed If set, then the value of the variable; otherwise, the default value
     */
    public static function isSet($value, $default)
    {
        // PHP 7
        // return $value ?? $default;

        return isset($value) ? $value : $default;
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
        return mb_check_encoding($value, self::$defaultCharset);
    }

    /**
     * Redirect to a url
     *
     * @access public
     * @param string $url Url to redirect to
     * @param boolean $validate Validate the url being redirected to. Default is true
     * @return undefined
     */
    public static function redirect($url, $validate = true)
    {
        // Ensure $validate is always true by default if a boolean datatype isn't passed
        if ($validate !== false && !self::isURL($url)) {
            return;
        }

        header("Location: $url");
    }

    /**
     * Get the request body data
     * URL: http://php.net/manual/en/wrappers.php.php#wrappers.php.input
     *
     * @access public
     * @return mixed|null Request body data; otherwise, null on error
     */
    public static function requestBody()
    {
        static $_input;

        // Cache the request body
        if (!isset($_input)) {
            $_input = file_get_contents('php://input');
            if ($_input === false) {
                $_input = null;
            }
        }

        return $_input;
    }

    /**
     * Get the request body as a JSON object
     *
     * @access public
     * @param mixed $default Default value to return if an error occurs. Default is null
     * @return object|null JSON object; otherwise, $default on error
     */
    public static function requestJSON($default = null)
    {
        $contents = self::requestBody();

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
        if ($length === 0 || mb_strwidth($value) <= $length) {
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
        return mb_strtolower($value, self::$defaultCharset);
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
        return mb_convert_case($value, MB_CASE_TITLE, self::$defaultCharset);
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
        return mb_strtoupper($value, self::$defaultCharset);
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

    /**
     * Coerce a value to an array if not already an array
     *
     * @access public
     * @param mixed $value Value to coerce
     * @return array An new array with the zeroth element as the passed value; otherwise, the original array reference
     */
    public static function toArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }
}
