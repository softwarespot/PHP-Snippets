<?php

/**
 * Get the request method
 *
 * @access public
 * @param boolean $toUpperCase Convert the method to upper-case if true; otherwise, lower-case if false. Default is true
 * @return string Formatted request method
 */
function getRequestMethod($toUpperCase = true)
{
    $request = $_SERVER['REQUEST_METHOD'];

    return $toUpperCase === false ? strtolower($request) : strtoupper($request);
}
