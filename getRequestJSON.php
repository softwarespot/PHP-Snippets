<?php

/**
 * Get the request body as a JSON object
 *
 * @return object JSON object
 */
function getRequestJSON()
{
    $contents = file_get_contents('php://input');

    return json_decode($contents);
}
