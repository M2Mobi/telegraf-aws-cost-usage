<?php
/**
 * This file contains functions related to cli
 */

/**
 * Parse and validate cli options
 * Return TRUE if valid, FALSE otherwise
 *
 * @param string $config_file  Variable to contain the config file option
 * @param string $target_month Variable to containt the target month option
 *
 * @return boolean
 */
function get_cli_options(&$config_file, &$target_month)
{
    // default values
    $config_file  = '';
    $target_month = 'current';

    // parse options
    $options = getopt('c:m:');

    if (isset($options['c'])) {
        $config_file = $options['c'];
    }

    if (isset($options['m'])) {
        $target_month = $options['m'];
    }

    // validate option values
    if (! file_exists($config_file)) {
        log_error("Invalid config file path $config_file");
        return FALSE;
    }

    if (! in_array($target_month, [ 'current', 'previous' ])) {
        log_error("Invalid target month $target_month, must be either 'current' or 'previous'");
        return FALSE;
    }

    return TRUE;
}

/**
 * Log an error message
 *
 * @param string $message Error message
 *
 * @return void
 */
function log_error($message)
{
    $date = date('Y-m-d H:i:s');

    error_log("[$date] $message");
}

?>
