<?php

/**
 * Check if the request was an ajax request
 *
 * @return boolean True, the request was an ajax request; otherwise, false
 */
function isAjaxRequest()
{
    $request = $_SERVER['HTTP_X_REQUESTED_WITH'];

    return !empty($request) &&
        strtolower($request) === 'xmlhttprequest';
}
