<?php
/**
 * This file contains functions to parse a report file and output the data
 */

/**
 * Parse a report file and apply a function on each data line
 * Return FALSE in case of error
 *
 * @param string   $path     Report path (uncompressed)
 * @param callable $function Function to apply to each report line
 *                           first parameter is the line data (as array)
 *                           second parameter is the headers data (as array)
 *
 * @return void|boolean
 */
function parse_report($path, $function)
{
    $handle = fopen($path, 'r');

    if ($handle === FALSE) {
        error_log("Can't read report file $path");
        return FALSE;
    }

    $headers = fgetcsv($handle);

    while (($line_data = fgetcsv($handle)) !== FALSE) {
        call_user_func($function, $line_data, $headers);
    }

    fclose($handle);
}

?>
