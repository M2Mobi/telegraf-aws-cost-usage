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

    $headers = array_flip(fgetcsv($handle));

    while (($line_data = fgetcsv($handle)) !== FALSE) {
        call_user_func($function, $line_data, $headers);
    }

    fclose($handle);
}


/**
 * Output a report line using InfluxDB line format
 *
 * @param array $line    Report data of one line
 * @param array $headers Headers name to index mapping
 *
 * @return void
 */
function output_line_influxdb($line, $headers)
{
    $measurement = 'aws_cost_usage';

    $timestamp = strtotime($line[$headers['lineItem/UsageEndDate']]);

    $tags = [
        'account_id'     => $line[$headers['lineItem/UsageAccountId']],
        'product_code'   => $line[$headers['lineItem/ProductCode']],
        'resource_id'    => $line[$headers['lineItem/ResourceId']],
        'usage_type'     => $line[$headers['lineItem/UsageType']],
        'operation'      => $line[$headers['lineItem/Operation']],
        'transfer_type'  => $line[$headers['product/transferType']],
        'product_family' => $line[$headers['product/productFamily']]
    ];

    $fields = [
        'blended_cost'   => $line[$headers['lineItem/BlendedCost']],
        'unblended_cost' => $line[$headers['lineItem/UnblendedCost']],
        'usage_amount'   => $line[$headers['lineItem/UsageAmount']],
    ];

    echo influxdb_point_format($measurement, $timestamp, $fields, $tags);
}

/**
 * Return the line format for an InfluxDB point
 *
 * @param string $measurement Measurement name
 * @param int    $timestamp   Unix timestamp of the data point in seconds
 * @param array  $fields      All field key-value pairs for the point
 * @param array  $tags        All tag key-value pairs for the point
 *
 * @return string
 */
function influxdb_point_format($measurement, $timestamp, $fields, $tags =[])
{
    $result = $measurement;

    foreach ($tags as $k => $v) {
        if ($v !== '') {
            $result .= ",$k=" . str_replace(' ', '\ ', $v);
        }
    }

    $result .= ' ';

    $f = [];
    foreach ($fields as $k => $v) {
        if ($v !== '') {
            $f[] = "$k=$v";
        }
    }
    $result .= implode(',', $f);

    $result .= ' ';
    $result .= $timestamp . '000000000'; // nanoseconds
    $result .= "\n";

    return $result;
}

?>
