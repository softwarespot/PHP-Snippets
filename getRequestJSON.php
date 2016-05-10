<?php

/**
 * Get the request body as a JSON object
 *
 * @access public
 * @param mixed $default Default value to return if an error occurs. Default is null
 * @return object|null JSON object; otherwise, $default on error
 */
function getRequestJSON($default = null)
{
    // Cache the request body
    static $_contents = null;

    // Cache the request body if not done so already
    if ($_contents === null) {
        $_contents = file_get_contents('php://input');
    }

    return $_contents === false ? $default : json_decode($_contents);
}
