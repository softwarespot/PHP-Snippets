<?php

namespace Utils;

// START: Example
// Use the following namespace
use Utils\Utils;

$utf8String = 'In linguistics, umlaut (from German "sound alteration") is a sound change in which a vowel is pronounced more like a following vowel or semivowel. (ö ü) - Wikipedia, 2016';
Utils::var_dump(Utils::clientIPAddress(), 'clientIPAddress');
Utils::var_dump(Utils::guid(), 'guid');
Utils::var_dump(Utils::isFloat(100), 'isFloat');
Utils::var_dump(Utils::isInteger(100), 'isInteger');
Utils::var_dump(Utils::isPHP('5.6'), 'isPHP');
Utils::var_dump(Utils::isUTF8($utf8String), 'isUTF8');
Utils::var_dump(Utils::strCompact($utf8String, 40), 'strCompact');
Utils::var_dump(Utils::strToLower($utf8String), 'strToLower');
Utils::var_dump(Utils::strToUpper($utf8String), 'strToUpper');
Utils::var_dump(Utils::toArray(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), 'toArray');

$class = new \stdClass;
$class->foo = 100;
$class->bar = 200;
Utils::var_dump(Utils::toArray($class), 'toArray');

Utils::var_dump($_GET, '$_GET');
Utils::var_dump($_SERVER, '$_SERVER');
Utils::var_dump(Utils::parseQueryParams('http://example.com/index.php?key_1=value1&key_2=value2&key_3=value3'), 'parseQueryParams');
Utils::var_dump(Utils::parseQueryParams('http://example.com/index.php'), 'parseQueryParams :: Error');
// END: Example

// TODO:
// Add: Remove diacritics, URL:  https://github.com/johnstyle/php-utils/blob/master/src/Johnstyle/PhpUtils/String.php#L129
// Add: strEndsWith, URL: https://github.com/dontdrinkandroot/utils.php/blob/master/src/Dontdrinkandroot/Utils/StringUtils.php
// Add: strStartsWith, URL: https://github.com/dontdrinkandroot/utils.php/blob/master/src/Dontdrinkandroot/Utils/StringUtils.php
// Add: mb_internal_encoding usage
// Useful idea: URL: https://github.com/JBZoo/Utils

/**
 * A set of static utility functions
 *
 * Note: All string functions support UTF-8 strings, unless Utils::encoding is overridden with another character set
 * Style: The coding style for this utility class is PSR-2
 */
class Utils
{
    // Constants
    const IP_ADDRESS_V4 = 'ipv4';
    const IP_ADDRESS_V6 = 'ipv4';

    /**
     * Default character encoding for mb_* functions
     * @var string
     */
    protected static $encoding = 'UTF-8';

    /**
     * Get a value from an array based on a particular key
     *
     * @access public
     * @param mixed $needle Key to search for
     * @param array $haystack Array to search within
     * @param mixed $default Default value if not found. Default is null
     * @return mixed|null The value from the array; otherwise, $default on error
     */
    public static function arrayGet($needle, array &$haystack, $default = null)
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
        // Maybe use this in the future, URL: http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
        // Or URL: https://github.com/paste/Utils/blob/master/src/Paste/Utils.php#L165

        return static::isIPAddress($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * Get the content type
     *
     * @return string|null Content type string; otherwise, null on error
     */
    public static function contentType()
    {
        $contentType = $_SERVER['CONTENT_TYPE'];

        return empty($contentType) ? null : $contentType;
    }

    /**
     * Dump and die (aka exit)
     *
     * @access public
     * @see dump() for more details
     */
    public static function dd($data, $label = 'dump')
    {
        echo static::var_dump($data, $label, false);
        exit;
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
        // Check if the value is falsy
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            // There is very little performance difference between using $key => $value and array_keys()
            $keys = array_keys($value);

            foreach ($keys as $key) {
                $value[$key] = html_escape($value[$key], $doubleEncode);
            }

            return $value;
        }

        return htmlspecialchars($value, ENT_QUOTES, static::$encoding, $doubleEncode);
    }

    /**
     * Check if the request was via ajax
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
    public static function isFloat($value)
    {
        $reIsFloat = '/(?:^-?(?!0{2,})\d+\.\d+$)/';

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
    public static function isInteger($value)
    {
        $reIsInteger = '/(?:^-?(?!0+)\d+$)/';

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
        // Check if the value is falsy
        if (empty($ip)) {
            return false;
        }

        $ip = strtolower($type);

        switch ($ip) {
            case static::IP_ADDRESS_V4:
                $type = FILTER_FLAG_IPV4;
                break;

            case static::IP_ADDRESS_V6:
                $type = FILTER_FLAG_IPV6;
                break;

            default:
                $type = FILTER_FLAG_IPV4;
                break;
        }

        // Ensure $exludePrivAndRes is false by default by explicitly checking the type and value
        if ($exludePrivAndRes === true) {
            // Use bitwise OR when excluding the private and reserved address ranges
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
    public static function isSetVar($value, $default)
    {
        // PHP 7
        return $value ?? $default;

        // PHP 5.6
        // return isset($value) ? $value : $default;
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
        return mb_check_encoding($value, static::$encoding);
    }

    /**
     * Parse query parameters in a url string as an array
     * Note: This is a wrapper for parse_str, because of ... URL: http://phpsadness.com/sad/27
     *
     * @param string $url URL string to parse
     * @return array|null Parsed query parameters as an associative array; otherwise, null on error
     */
    public static function parseQueryParams($url)
    {
        if (empty($url)) {
            return null;
        }

        $queryString = parse_url($url, PHP_URL_QUERY);
        parse_str($queryString, $queryParams);

        return empty($queryParams) ? null : $queryParams;
    }

    /**
     * Redirect to a url
     *
     * @access public
     * @param string $url Url to redirect to
     * @param boolean $permanant True to set the header to 'Moved Permanently'. Default is false
     * @param boolean $validate Validate the url being redirected to. Default is true
     * @return undefined
     */
    public static function redirect($url, $permanant = false, $validate = true)
    {
        // Ensure $validate is always true by default if a boolean datatype isn't passed
        if ($validate !== false && !static::isURL($url)) {
            return;
        }

        // Enforce the default value of false
        if ($permanant === true) {
            header('HTTP/1.1 301 Moved Permanently');
        }

        header("Location: $url");
        exit;
    }

    /**
     * Retrieve the request body data
     * URL: http://php.net/manual/en/wrappers.php.php#wrappers.php.input
     *
     * @access public
     * @return mixed|null Request body data; otherwise, null on error
     */
    public static function requestBody()
    {
        // Cache the request body
        static $_input;

        // Cache the request body if not done already
        if (!isset($_input)) {
            $_input = file_get_contents('php://input');
            if ($_input === false) {
                $_input = null;
            }
        }

        return $_input;
    }

    /**
     * Retrieve the $_GET request array with an optional key
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_GET array
     * @return array|mixed Value of the key or a deep clone of the $_GET array; otherwise, null or an empty array on error
     */
    public static function requestGET($key = null)
    {
        return static::_arrayFetchAll($_GET, $key);
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
        $contents = static::requestBody();

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
        $method = $_SERVER['REQUEST_METHOD'];

        return $toUpperCase === false ? strtolower($method) : strtoupper($method);
    }

    /**
     * Retrieve the $_POST request array with an optional key
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_POST array
     * @return array|mixed Value of the key or a deep clone of the $_POST array; otherwise, null or an empty array on error
     */
    public static function requestPOST($key = null)
    {
        return static::_arrayFetchAll($_POST, $key);
    }

    /**
     * Retrieve the $_REQUEST request array with an optional key
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_REQUEST array
     * @return array|mixed Value of the key or a deep clone of the $_REQUEST array; otherwise, null or an empty array on error
     */
    public static function requestREQUEST($key = null)
    {
        return static::_arrayFetchAll($_REQUEST, $key);
    }

     /**
     * Retrieve the $_SERVER request array with an optional key
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_SERVER array
     * @return array|mixed Value of the key or a deep clone of the $_SERVER array; otherwise, null or an empty array on error
     */
    public static function requestSERVER($key = null)
    {
        return static::_arrayFetchAll($_SERVER, $key);
    }

    /**
     * Clean UTF-8 strings so it contains only valid characters as set
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Utf8.php
     *
     * @access public
     * @param string $str String to clean
     * @return string Cleaned string
     */
    public static function strClean($str)
    {
        return mb_convert_encoding($str, static::$encoding, static::$encoding);
    }

    /**
     * Compact a string to a maximum length
     *
     * @access public
     * @param string $str String to compact
     * @param integer $length Length to trim at
     * @return string Compact string; otherwise, original string
     */
    public static function strCompact($str, $length = 0)
    {
        // mb_strwidth is better than using mb_strlen. See PHP docs for more details
        if ($length === 0 || mb_strwidth($str) <= $length) {
            return $str;
        }

        return mb_strimwidth($str, 0, $length, '...');
    }

    /**
     * Retrieve the length of a given string
     *
     * @access public
     * @param string $str String to check
     * @return integer String value length
     */
    public static function strLen($str)
    {
        return mb_strlen($str);
    }

    /**
     * Convert a snake case string to camel case string
     * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
     *
     * @access public
     * @param string $str Snake case string
     * @return string String as camel case
     */
    public static function strToCamelCase($str)
    {
        $str = str_replace(['-', '_'], ' ', $str);
        $str = ucwords($str);
        $str =  str_replace(' ', '', $str);

        return lcfirst($str);
    }

    /**
     * Convert a string to lower-case
     *
     * @access public
     * @param string $str String to change to lower-case
     * @return string Lower-case string
     */
    public static function strToLower($str)
    {
        return mb_strtolower($str, static::$encoding);
    }

    /**
     * Convert a string to title-case
     *
     * @access public
     * @param string $str String to change to title-case
     * @return string Title-case string
     */
    public static function strToTitle($str)
    {
        return mb_convert_case($str, MB_CASE_TITLE, static::$encoding);
    }

    /**
     * Convert a string to upper-case
     *
     * @access public
     * @param string $str String to change to upper-case
     * @return string Upper-case string
     */
    public static function strToUpper($str)
    {
        return mb_strtoupper($str, static::$encoding);
    }

    /**
     * Convert a string to a snake case string
     * Idea by Laravel, URL: https://github.com/laravel/framework/blob/master/src/Illuminate/Support/Str.php
     *
     * @access public
     * @param string $str Camel case string
     * @param string $delimiter Delimiter to use. Default '_'
     * @return string String as snake case
     */
    public static function strToSnakeCase($str, $delimiter = '_')
    {
        // Pre-append the delimiter before an upper-case character
        $str = preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $str);

        return mb_strtolower($str);
    }

    /**
     * Coerce a value to an array if not already an array
     *
     * @access public
     * @param mixed $value0...n Value(s) to coerce
     * @return array An new array with the zeroth element as the passed value; otherwise, the original array reference
     */
    public static function toArray($value /*, $value1, $value2, $valuen*/)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        $args = [];
        foreach (func_get_args() as $arg) {
            $args[] = $arg;
        }

        return $args;
    }

    /**
     * Convert an array to a comma separated value (CSV) string
     * Idea by metashock, URL: http://www.metashock.de/2014/02/create-csv-file-in-memory-php/
     * TODO: Consider passing the headers as an additional argument
     *
     * @access public
     * @param mixed $data Data to convert
     * @param string $delimiter The optional delimiter parameter sets the field delimiter (one character only). Null will use the default value (,)
     * @param string $enclosure The optional enclosure parameter sets the field enclosure (one character only). Null will use the default value (")
     * @return string A CSV string; otherwise, null on error
     */
    public static function toCSV($data, $delimiter = ',', $enclosure = '"')
    {
        // Use a threshold of 1 MB (1024 * 1024)
        $handle = fopen('php://temp/maxmemory:1048576', 'w');
        if ($handle === false) {
            return null;
        }

        // Check the default arguments
        if ($delimiter === null) {
            $delimiter = ',';
        }

        if ($enclosure === null) {
            $enclosure = '"';
        }

        // Cast as an array if not already
        if (!is_array($data)) {
            $data = (array) $data;
        }

        // Check if it's a multi-dimensional array
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys($data[0]);
        } else {
            // Single array
            $headings = array_keys($data);
            $data = [$data];
        }

        // Apply the headings
        fputcsv($handle, $headings, $delimiter, $enclosure);

        foreach ($data as $record) {
            // If the record is not an array, then break. This is because the 2nd param of
            // fputcsv() should be an array
            if (!is_array($record)) {
                break;
            }

            // Suppressing the "array to string conversion" notice. Retain the "evil" @ here.
            $record = @array_map('strval', $record);

            // Returns the length of the string written or false
            fputcsv($handle, $record, $delimiter, $enclosure);
        }

        // Reset the file pointer
        rewind($handle);

        // Retrieve the csv contents
        $csv = stream_get_contents($handle);

        fclose($handle);

        return $csv;
    }

    /**
     * Dump variable data in a clear format
     * Idea by Joost van Veen, URL: https://gist.github.com/accentinteractive/3838495
     *
     * @access public
     * @param mixed $data Data to dump
     * @param string $label Label to output next to the data. Default is 'dump'
     * @param boolean $echo True to echo to the output or return as a buffer string
     * @return string|undefined If $echo is true, then the data is output; otherwise, a buffer string is returned
     */
    public static function var_dump($data, $label = 'dump', $echo = true)
    {
        // Store dump data in a buffer
        ob_start();
        var_dump($data);
        $output = ob_get_clean();

        // Add appropriate formatting
        $reAddWS = '/\]\=\>\n(\s+)/m';
        $output = preg_replace($reAddWS, '] => ', $output);
        $output = "<pre style=\"background: #3498db; color: #000; border: 1px dotted #000; margin: 10px 0; padding: 10px; text-align: left; white-space: pre-wrap;\">$label => $output</pre>";

        // Return the current buffer, maintaining the default value
        if ($echo === false) {
            return $output;
        }

        echo $output;
    }

    // Internal functions

    /**
     * Get a value from an array based on a particular key or a deep cloned array
     *
     * @access public
     * @param array $haystack Array to search within
     * @param mixed|null $needle Optional key to search for. If left null/undefined, then the entire array is deep cloned
     * @return array|mixed Either a deep cloned array or the value of the key; otherwise an empty array or null on error
     */
    private static function _arrayFetchAll(array &$haystack, $needle = null)
    {
        // If null/undefined, then assume the array should be deep cloned
        if (!isset($needle)) {
            $needle = array_keys($haystack);
        }

        // Enumerate over the array copying all values, including nested arrays
        if (is_array($needle)) {
            $array = [];

            foreach ($needle as $key) {
                $array[$key] = static::_arrayFetchAll($haystack, $key);
            }

            return $array;
        }

        if (isset($haystack[$needle])) {
            return $haystack[$needle];
        }

        return null;
    }
}
