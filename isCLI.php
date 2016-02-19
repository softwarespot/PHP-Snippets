<?php

/**
 * Is the command-line interface (CLI)
 * Idea by PHP, URL: http://php.net/manual/en/features.commandline.php
 *
 * @access public
 * @return boolean True, using the CLI; otherwise, false
 */
function isCLI()
{
    return (PHP_SAPI === 'cli' || defined('STDIN'));
}
