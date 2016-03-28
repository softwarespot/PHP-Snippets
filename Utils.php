<?php

namespace App;

/**
 * A set of static utility functions
 *
 * Note: All string functions support UTF-8 strings, unless Utils::$encoding is overridden with another character set
 * Style: The coding style for this utility class is PSR-2 with all functions being named using camel-case
 */
class Utils
{
    // Constants
    const IP_ADDRESS_V4 = 'ipv4';
    const IP_ADDRESS_V6 = 'ipv6';
    const STR_EMPTY = '';

    /**
     * Default character encoding for mb_* functions
     * @var string
     */
    protected static $encoding = 'UTF-8';

    /**
     * Locked filepaths
     * @var array
     */
    protected static $locks = [];

    /**
     * List of supported formats aka content types
     * @var array
     */
    protected static $supportedContentTypes = [
        'json' => 'application/json',
        'jsonp' => 'application/javascript',
        'text' => 'text/plain',
    ];

    /**
     * Remove all null/empty/false values from an array
     *
     * @access public
     * @param  array $array Array to clean
     * @return array Cleaned array
     */
    public static function arrayClean(array $array)
    {
        return array_filter($array);
    }

    /**
     * Filter out keys and their respective values from an array
     *
     * @access public
     * @param array $haystack Array to filter
     * @param array $keys String array of keys to filter out
     * @return array Filtered array
     */
    public static function arrayFilterKeys(array $haystack, array $keys)
    {
        return array_filter($haystack, function ($key) use ($keys) {
            return !in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Flatten a deep nested array
     * Idea by nette, URL: https://github.com/nette/utils/blob/master/src/Utils
     *
     * @access public
     * @param  array $array Array to flatten
     * @param  boolean $preserveKeys Preserve the keys. Default is false
     * @return array Flattened array
     */
    public static function arrayFlatten(array $array, $preserveKeys = false)
    {
        $flattened = [];
        $cb = null;

        // Enforce the default value
        if ($preserveKeys === true) {
            $cb = function ($value, $key) use (&$flattened) {
                $flattened[$key] = $value;
            };
        } else {
            $cb = function ($value) use (&$flattened) {
                $flattened[] = $value;
            };
        }

        array_walk_recursive($array, $cb);

        return $flattened;
    }

    /**
     * Get the first value in an array
     *
     * @access public
     * @param array &$array Array to get the first value of
     * @return mixed First value
     */
    public static function arrayFirst(array &$array)
    {
        return reset($array);
    }

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
     * Get the last value in an array
     *
     * @access public
     * @param array &$array Array to get the last value of
     * @return mixed Last value
     */
    public static function arrayLast(array &$array)
    {
        return end($array);
    }

    /**
     * Set a value in an array using a key
     *
     * @access public
     * @param array &$array Array to set
     * @param mixed $key Key to set
     * @param mixed $value Value to set with
     * @return void
     */
    public static function arraySet(array &$array, $key, $value)
    {
        $array[$key] = $value;
    }

    /**
     * Create an autoloader for when an unloaded class is instantiated
     *
     * @access public
     * @param  array $paths Paths to search within
     * @return void
     */
    public static function autoloader(array $paths)
    {
        $extension = '.php';

        // Create an anonymous function referencing the outer variables
        spl_autoload_register(function ($class) use ($paths, $extension) {
            // Check if the class has already been loaded
            if (class_exists($class, false)) {
                return;
            }

            foreach ($paths as $path) {
                // Sanitize the filepath
                $filePath = realpath($path . '/' . $class . $extension);

                if (is_file($filePath)) {
                    // Require will produce an E_COMPILE_ERROR if the file doesn't exist,
                    // whereas include will throw a warning
                    require_once($filePath);
                    break;
                }
            }
        });
    }

    /**
     * Get the client's IP address
     * Idea by CodeIgniter, URL: https://github.com/bcit-ci/CodeIgniter/blob/master/system/core/Input.php
     *
     * @access public
     * @param boolean $proxy Check the IP address if behind a proxy. Default is false
     * @return string Client's IP address; otherwise, null on error
     */
    public static function clientIPAddress($proxy = false)
    {
        $ip = self::requestSERVER('REMOTE_ADDR');

        // Enforce the default value
        if ($proxy === true) {
            $headers = [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_CLIENT_IP',
                'HTTP_X_CLUSTER_CLIENT_IP'
            ];

            foreach ($headers as $header) {
                $proxyIP = self::requestSERVER($header);
                if ($proxyIP !== null) {
                    // Some proxies typically list the whole chain of IP addresses through
                    // which the client has reached us
                    sscanf($proxyIP, '%[^,]', $proxyIP);
                    if (self::isIPAddress($proxyIP)) {
                        return $proxyIP;
                    }
                }
            }
        }

        return self::isIPAddress($ip) ? $ip : null;
    }

    /**
     * Get the supported content type
     *
     * @access public
     * @return string|null Content type string; otherwise, null on error
     */
    public static function contentType()
    {
        $contentType = self::requestSERVER('CONTENT_TYPE');

        // If separated by semi-colons, then get the first part of the content type string
        if (strpos($contentType, ';')) {
            $contentType = current(explode(';', $contentType));
        }

        $found = array_search($contentType, self::$supportedContentTypes) !== false;

        return $found ? $contentType : null;
    }

    /**
     * Get the contents of a url using a curl request
     *
     * @access public
     * @param string $url URL to get the contents of
     * @param array|null $options Optional curl options passed to curl_setopt()
     * @param array $options Optional array of allowed HTTP status codes. Default is HTTP_OK (200)
     * @return string|null String contents of the url; otherwise, null on error
     */
    public static function curlGet($url, $options = null, $allowed = [200])
    {
        if (!self::isURL($url)) {
            return null;
        }

        $request = curl_init($url);

        // Set the additional options for the request
        if (is_array($options)) {
            curl_setopt_array($request, $options);
        }

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        // curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($request);

        $error = curl_error($request);
        if ($error) {
            throw new \Exception("Curl: Error code was $error");
        } else {
            $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
            if (!in_array($statusCode, $allowed)) {
                throw new \Exception("Curl: Invalid response from $url\nHttpResponse: $statusCode\n$contents\n");
            }
        }

        curl_close($request);

        return $contents;
    }

    /**
     * Dump and die (aka exit)
     *
     * @access public
     * @see dump() for more details
     */
    public static function dd($data, $label = 'dump')
    {
        echo self::var_dump($data, $label, false);
        exit;
    }

    /**
    * Builds a filepath with the appropriate directory separator
    *
    * @access public
    * @param string $value0...n Unlimited number of parts e.g. fileBuildPath('C:', 'dir_0', 'dir_1');
    * @return string Built filepath
    */
    public static function fileBuildPath()
    {
        // PHP 5.6+ use ...$parts instead
        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }

    /**
     * Write to a filepath
     *
     * @access public
     * @param string $filePath Filepath to write data to
     * @param  string $data Data to append or overwrite the file with
     * @param  boolean $overwrite Overwrite the file contents. Default is false
     * @return boolean True, the file was written to; otherwise, false
     */
    public static function fileWrite($filePath, $data, $overwrite = false)
    {
        $flags = LOCK_EX;

        // Enforce the default value
        if ($overwrite !== true) {
            $flags |= FILE_APPEND;
        }

        return (boolean) file_put_contents($filePath, $data, $flags);
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
                $value[$key] = self::html_escape($value[$key], $doubleEncode);
            }

            return $value;
        }

        // Enforce the default value of false
        is_bool($doubleEncode) || $doubleEncode = false;

        return htmlspecialchars($value, ENT_QUOTES, self::$encoding, $doubleEncode);
    }

    /**
     * Check if the request was via ajax
     *
     * @access public
     * @return boolean True, the request was an ajax request; otherwise, false
     */
    public static function isAjaxRequest()
    {
        $request = self::requestSERVER('HTTP_X_REQUESTED_WITH');

        return !empty($request) && strtolower($request) === 'xmlhttprequest';
    }

    /**
     * Check if an array is associative
     * Idea by goldenSniperOS, URL: https://github.com/goldenSniperOS/api-participo/blob/90f123ecc300adae95e7ec353866c76c93b36449/app/functions/assoc.php
     * @access public
     * @param array $array Array to check
     * @return boolean True, the array is associative; otherwise, false
     */
    public static function isArrayAssoc(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
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
     * Check if an image url is a valid image url e.g. ends with .gif or .jpg
     *
     * @access public
     * @param string $image Image url to check
     * @return boolean True, is a valid image url; otherwise, false
     */
    public static function isImage($image)
    {
        $reIsImage = '/(?:\.(?:gif|jpe?g|png|svg|tif|webp)$)/i';
        $image = parse_url((string) $image, PHP_URL_PATH);

        return (bool) preg_match($reIsImage, $image);
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
     * @param string $type IP protocol: Utils::IP_ADDRESS_V4 ('ipv4') or Utils::IP_ADDRESS_V6 ('ipv6').
     * Default is Utils::IP_ADDRESS_V4
     * @param boolean $exludePrivAndRes Exclude private and reserved ranges. Default it false
     * @return boolean True, is a valid IP address; otherwise, false
     */
    public static function isIPAddress($ip, $type = FILTER_FLAG_IPV4, $exludePrivAndRes = false)
    {
        // Check if the value is falsy
        if (empty($ip)) {
            return false;
        }

        $type = strtolower($type);

        switch ($type) {
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

        // Check the default value is boolean
        is_bool($exludePrivAndRes) || $exludePrivAndRes = false;

        if ($exludePrivAndRes) {
            // Use bitwise OR when excluding the private and reserved address ranges
            $type |= FILTER_FLAG_NO_PRIV_RANGE;
            $type |= FILTER_FLAG_NO_RES_RANGE;
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $type);
    }

    /**
     * Check if a variable is a valid JSON string
     * Idea by cherrylabs, URL: https://github.com/cherrylabs/arx-utils/blob/master/src/Arx/Utils/Utils.php
     *
     * @access public
     * @param string $str String to check
     * @return boolean True, is a valid JSON string; otherwise, false
     */
    public static function isJSON($str)
    {
        if (empty($str) || !is_string($str)) {
            return false;
        }

        json_decode($str);

        return json_last_error() === JSON_ERROR_NONE;
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
        // return $value ?? $default;

        // PHP 5
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
        return mb_check_encoding($value, self::$encoding);
    }

    /**
     * Create a locked file
     *
     * @access public
     * @param string $name Lock file name, that is appended with the .lock extension
     * @return boolean True, the lock file exists; otherwise, false
     */
    public static function lock($name)
    {
        $fp = fopen($name . '.lock', 'w+');
        if (!flock($fp, LOCK_EX|LOCK_NB)) {
            return true;
        }

        $locks[] = $fp;

        return false;
    }

    /**
     * Map an object to an associative array with an optional array of included keys
     *
     * @param object $obj Object to remap
     * @param array $include Optional array of keys to include. Default is include all keys
     * @return array An associative array
     */
    public static function objToArray($obj, $include = null)
    {
        $defaultInclude = count($include) === 0;

        $mapped = [];
        foreach ($obj as $key => $value) {
            if ($defaultInclude || in_array($key, $include)) {
                $mapped[$key] = is_array($value) || is_object($value) ? self::objToArray($value, $include) : $value;
            }
        }

        return $mapped;
    }

    /**
     * Parse query parameters in a url string as an array
     * Note: This is a wrapper for parse_str, because of ... URL: http://phpsadness.com/sad/27
     *
     * @access public
     * @param string $url URL string to parse
     * @return array|null Parsed query parameters as an associative array; otherwise, null on error
     */
    public static function parseQueryParams($url)
    {
        // Cast as a string
        $url = (string) $url;

        $queryString = parse_url($url, PHP_URL_QUERY);
        self::strParse($queryString, $queryParams);

        return $queryParams;
    }

    /**
     * Get the percentage difference between two values
     *
     * @access public
     * @param number $old Previous value
     * @param number $new Current value
     * @return string Percentage difference value
     */
    public static function percentDiff($old, $new)
    {
        return (($old - $new / $old) * 100) . '%';
    }

    /**
     * Redirect to a url
     *
     * @access public
     * @param string $url Url to redirect to
     * @param boolean $permanent True to set the header to 'Moved Permanently'. Default is false
     * @param boolean $validate Validate the url being redirected to. Default is true
     * @return void
     */
    public static function redirect($url, $permanent = false, $validate = true)
    {
        // Check the default value is boolean
        is_bool($validate) || $validate = true;

        // Ensure $validate is always true by default if a boolean datatype isn't passed
        if (!$validate && !self::isURL($url)) {
            return;
        }

        // Enforce the default value of false
        if ($permanent === true) {
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
        static $_input = null;

        // Cache the request body if not done already
        if ($_input === null) {
            $_input = file_get_contents('php://input');
        }

        return $_input === false ? null : $_input;
    }

    /**
     * Retrieve the DELETE request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the DELETE array
     * @return array|mixed Value of the key or a deep clone of the DELETE array; otherwise,
     * null or an empty array on error
     */
    public static function requestDELETE($key = null)
    {
        // Cache the $_DELETE 'global'
        static $_DELETE;
        if (!isset($_DELETE)) {
            self::strParse(self::requestBody(), $_DELETE, []);
        }

        return self::arrayFetchAll($key, $_DELETE);
    }

    /**
     * Retrieve the GET request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the GET array
     * @return array|mixed Value of the key or a deep clone of the GET array; otherwise, null or an empty array on error
     */
    public static function requestGET($key = null)
    {
        return self::arrayFetchAll($key, $_GET);
    }

    /**
     * Retrieve the HEAD request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_HEAD array
     * @return array|mixed Value of the key or a deep clone of the $_HEAD array; otherwise,
     * null or an empty array on error
     */
    public static function requestHEAD($key = null)
    {
        // Cache the $_HEAD 'global'
        static $_HEAD;
        if (!isset($_HEAD)) {
            self::strParse(self::requestSERVER('QUERY_STRING'), $_HEAD, []);
        }

        return self::arrayFetchAll($key, $_HEAD);
    }

    /**
     * Retrieve the request body as a JSON object
     *
     * @access public
     * @param mixed $default Default value to return if an error occurs. Default is null
     * @return object|null JSON object; otherwise, $default on error
     */
    public static function requestJSON($default = null)
    {
        $contents = self::requestBody();

        return $contents === null ? $default : json_decode($contents);
    }

    /**
     * Get the request method
     *
     * @access public
     * @param boolean $toUpperCase Convert the method to upper-case if true; otherwise,
     * lower-case if false. Default is true
     * @return string Formatted request method
     */
    public static function requestMethod($toUpperCase = true)
    {
        $method = self::requestSERVER('REQUEST_METHOD');

        return $toUpperCase === false ? strtolower($method) : strtoupper($method);
    }

    /**
     * Retrieve the PATCH request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the PATCH array
     * @return array|mixed Value of the key or a deep clone of the PATCH array; otherwise,
     * null or an empty array on error
     */
    public static function requestPATCH($key = null)
    {
        // Cache the $_PATCH 'global'
        static $_PATCH;
        if (!isset($_PATCH)) {
            self::strParse(self::requestBody(), $_PATCH, []);
        }

        return self::arrayFetchAll($key, $_PATCH);
    }

    /**
     * Retrieve the POST request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the POST array
     * @return array|mixed Value of the key or a deep clone of the POST array; otherwise,
     * null or an empty array on error
     */
    public static function requestPOST($key = null)
    {
        return self::arrayFetchAll($key, $_POST);
    }

    /**
     * Retrieve the PUT request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the PUT array
     * @return array|mixed Value of the key or a deep clone of the PUT array; otherwise, null or an empty array on error
     */
    public static function requestPUT($key = null)
    {
        // Cache the $_PUT 'global'
        static $_PUT;
        if (!isset($_PUT)) {
            self::strParse(self::requestBody(), $_PUT, []);
        }

        return self::arrayFetchAll($key, $_PUT);
    }

    /**
     * Retrieve the REQUEST array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the REQUEST array
     * @return array|mixed Value of the key or a deep clone of the REQUEST array; otherwise,
     * null or an empty array on error
     */
    public static function requestREQUEST($key = null)
    {
        return self::arrayFetchAll($key, $_REQUEST);
    }

    /**
     * Retrieve the $_SERVER request array with an optional key to retrieve a single value
     *
     * @access public
     * @param mixed $key Optional key to search for; otherwise, a deep clone of the $_SERVER array
     * @return array|mixed Value of the key or a deep clone of the $_SERVER array; otherwise,
     * null or an empty array on error
     */
    public static function requestSERVER($key = null)
    {
        return self::arrayFetchAll($key, $_SERVER);
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
        return mb_convert_encoding($str, self::$encoding, self::$encoding);
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
     * Check if a string contains a substring
     *
     * @access public
     * @param string $str String to search within
     * @param string $search String to search for
     * @param  boolean $caseSensitive True, case-sensitive matching; otherwise, false. Default is true
     * @return boolean True, the substring was found; otherwise, false
     */
    public static function strContains($str, $search, $caseSensitive = true)
    {
        // Enforce the default value of true
        if ($caseSensitive === false) {
            return mb_stripos($str, $search) !== false;
        }

        return mb_strpos($str, $search) !== false;
    }

    /**
     * Check if a substring begins at the end of a string
     *
     * @access public
     * @param string $str String to search within
     * @param string $search String to search for
     * @param  boolean $caseSensitive True, case-sensitive matching; otherwise, false. Default is true
     * @return boolean True, the substring begins at the end of the string; otherwise, false
     */
    public static function strEndsWith($str, $search, $caseSensitive = true)
    {
        // Enforce the default value of true
        is_bool($caseSensitive) || $caseSensitive = true;

        return substr_compare($str, $search, 0, -mb_strlen($search), $caseSensitive) === 0;
    }

    /**
     * Check if a string is empty (trims all whitespace)
     * Note: The different between this and empty(), is it checks if the type is a string
     *
     * @access public
     * @param string $str String to check
     * @return boolean True, the string is empty (or not a string); otherwise, false
     */
    public static function strIsEmpty($str)
    {
        // If it's not a string by default, then set as being 'empty' i.e. true
        if (!is_string($str)) {
            return true;
        }

        return Utils::strLen(trim($str)) === 0;
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
     * Standardize line endings to unix-like.
     *
     * @access public
     * @param  string  UTF-8 encoding or 8-bit
     * @return string
     */

    /**
     * Standardize line endings to unix-like
     * Idea by nette, URL: https://github.com/nette/utils/blob/master/src/Utils
     *
     * @access public
     * @param string $str String to normalize line endings
     * @return string Normalized string
     */
    public static function strNormalizeEOL($str)
    {
        return str_replace(["\r\n", "\r"], "\n", $str);
    }

    /**
     * Parse a simple template string using Mustache-like syntax e.g. {{example}}
     *
     * @access public
     * @param string $template Template string to parse
     * @param array $context An associative array of key/values pairs to replace in the template string
     * @return string Parsed template string; otherwise, empty string on error
     */
    public static function strParseTemplate($template, array $context)
    {
        if (!is_string($template)) {
            return STR_EMPTY;
        }

        // Build a replacement array with curly braces around the keys
        $replace = [];
        foreach ($context as $key => $value) {
            $replace["{{{$key}}}"] = $value;
        }

        // Interpolate the replacement values in the template
        return strtr($template, $replace);
    }

    /**
     * Sanitize an e-mail string
     *
     * @access public
     * @param string $email E-mail string to sanitize
     * @return string Sanitized e-mail string
     */
    public static function strSanitizeEmail($email)
    {
        return filter_var((string) $email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize a string
     *
     * @access public
     * @param string $str String to sanitize
     * @return string|null Sanitized string; otherwise, throws an InvalidArgumentException exception
     */
    public static function strSanitize($str)
    {
        if (is_string($str)) {
            self::htmlEscape($str);
        }

        throw new \InvalidArgumentException('Invalid argument, was not a string datatype');
    }

    /**
     * Retrieve part of a string
     *
     * @access public
     * @see http://php.net/manual/en/function.mb-substr.php or more details
     */
    public static function strSubtr($str, $start, $length = null)
    {
        return mb_substr($str, $start, $length, self::$encoding);
    }

    /**
     * Check if a substring begins at the start of a string
     *
     * @access public
     * @param string $str String to search within
     * @param string $search String to search for
     * @param  boolean $caseSensitive True, case-sensitive matching; otherwise, false. Default is true
     * @return boolean True, the substring begins at the start of the string; otherwise, false
     */
    public static function strStartsWith($str, $search, $caseSensitive = true)
    {
        // Enforce the default value of true
        is_bool($caseSensitive) || $caseSensitive = true;

        return substr_compare($str, $search, 0, mb_strlen($search), $caseSensitive) === 0;
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
        $str =  str_replace(' ', STR_EMPTY, $str);

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
        return mb_strtolower($str, self::$encoding);
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
        return mb_convert_case($str, MB_CASE_TITLE, self::$encoding);
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
        return mb_strtoupper($str, self::$encoding);
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
        $reToSnakeCase = '/(.)(?=[A-Z])/';

        // Pre-append the delimiter before an upper-case character
        $str = preg_replace($reToSnakeCase, '$1' . $delimiter, $str);

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

        // Cast an object as an array
        if (is_object($value)) {
            return (array) $value;
        }

        $array = [];

        $args = func_get_args();
        foreach ($args as $arg) {
            $array[] = $arg;
        }

        return $array;
    }

    /**
     * Convert an array to a comma separated value (CSV) string
     * Idea by metashock, URL: http://www.metashock.de/2014/02/create-csv-file-in-memory-php/
     * TODO: Consider passing the headers as an additional argument
     *
     * @access public
     * @param mixed $data Data to convert
     * @param string $delimiter The optional delimiter parameter sets the field delimiter
     * (one character only). Null will use the default value (,)
     * @param string $enclosure The optional enclosure parameter sets the field enclosure
     * (one character only). Null will use the default value (")
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

        $headings = null;

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

            // Suppressing the "array to string conversion" notice. Retain the "evil" @ here
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
     * @return string|void If $echo is true, then the data is output; otherwise, a buffer string is returned
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
     * @access private
     * @param mixed|null $needle Optional key to search for. If left null/void, then the entire array is deep cloned
     * @param array $haystack Array to search within
     * @param mixed $default Default value if not found. Default is null
     * @return array|mixed Either a deep cloned array or the value of the key; otherwise an empty array or null on error
     */
    private static function arrayFetchAll($needle, &$haystack, $default = null)
    {
        // If null/void, then assume the array should be deep cloned
        if (!isset($needle)) {
            $needle = array_keys($haystack);
        }

        // Enumerate over the array copying all values, including nested arrays
        if (is_array($needle)) {
            $array = [];

            foreach ($needle as $key) {
                $array[$key] = self::arrayFetchAll($key, $haystack);
            }

            return $array;
        }

        // A reliable approach to checking if a key exists in the array
        if (isset($haystack[$needle]) || array_key_exists($needle, $haystack)) {
            return $haystack[$needle];
        }

        return $default;
    }

    /**
     * Basic wrapper for parse_str which returns a default value on error. See parse_str docs for more details
     *
     * @access private
     * @param mixed $default Default value if not found. Default is null
     */
    private static function strParse($str, &$array, $default = null)
    {
        parse_str($str, $array);

        // Set to the default value if an error occurred
        if (!is_array($array)) {
            $array = $default;
        }
    }
}

// TODO List
// Add: Remove diacritics, URL:  https://github.com/johnstyle/php-utils/blob/master/src/Johnstyle/PhpUtils/String.php#L129
// Add: is*, URL: https://github.com/nette/utils/blob/master/src/Utils/Validators.php
// Useful links: URL:
// https://github.com/JBZoo/Utilshttps://github.com/nette/utils
// https://github.com/cherrylabs/arx-utils/tree/master/src/Arx/Utils
// https://github.com/dreamfactorysoftware/php-utils/blob/develop/src/Curl.php

// START: Example
/*
    // Use the following namespace
    // use App\Utils;

    $utf8String = 'In linguistics, umlaut (from German "sound alteration") is a sound change in which a vowel is pronounced more like a following vowel or semivowel. (ö ü) - Wikipedia, 2016';

    Utils::var_dump(Utils::clientIPAddress(), 'clientIPAddress');
    Utils::var_dump(Utils::contentType(), 'contentType');
    Utils::var_dump(Utils::guid(), 'guid');
    Utils::var_dump(Utils::isAjaxRequest() ? 'AJAX request' : 'Not an AJAX request', 'isAjaxRequest');
    Utils::var_dump(Utils::isFloat(100.99), 'isFloat');
    Utils::var_dump(Utils::isFloat(100), 'isFloat :: Error');
    Utils::var_dump(Utils::isInteger(100), 'isInteger');
    Utils::var_dump(Utils::isPHP('5.6'), 'isPHP');
    Utils::var_dump(Utils::isUTF8($utf8String), 'isUTF8');
    Utils::var_dump(Utils::parseQueryParams('http://example.com/index.php?key_1=value1&key_2=value2&key_3=value3'), 'parseQueryParams');
    Utils::var_dump(Utils::parseQueryParams('http://example.com/index.php'), 'parseQueryParams :: Error');
    Utils::var_dump(Utils::strCompact($utf8String, 40), 'strCompact');
    Utils::var_dump(Utils::strToLower($utf8String), 'strToLower');
    Utils::var_dump(Utils::strToUpper($utf8String), 'strToUpper');
    Utils::var_dump(Utils::toArray(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), 'toArray');

    // Cast an object to an array
    $obj = new \stdClass;
    $obj->foo = 'foo';
    $obj->bar = 'bar';
    Utils::var_dump(Utils::toArray($obj), 'toArray');

    // Dump all globals
    Utils::var_dump(Utils::requestDELETE(), 'requestDELETE');
    Utils::var_dump(Utils::requestGET(), 'requestGET');
    Utils::var_dump(Utils::requestHEAD(), 'requestHEAD');
    Utils::var_dump(Utils::requestPATCH(), 'requestPATCH');
    Utils::var_dump(Utils::requestPOST(), 'requestPOST');
    Utils::var_dump(Utils::requestPUT(), 'requestPUT');
    Utils::var_dump(Utils::requestREQUEST(), 'requestREQUEST');
    Utils::var_dump(Utils::requestSERVER(), 'requestSERVER');
*/
// END: Example
