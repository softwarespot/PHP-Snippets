<?php

/**
 * Get the request body as a JSON object
 *
 * @access public
 * @param mixed $default Default value if an error occurs. Default is null
 * @return object|null JSON object; otherwise, $default on error
 */
function getRequestJSON($default = null)
{
    $contents = file_get_contents('php://input');

    return $contents === false ? $default : json_decode($contents);
}
