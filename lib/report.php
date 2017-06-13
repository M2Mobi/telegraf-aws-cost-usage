<?php
/**
 * This file contains functions to parse a report file and output the data
 */

/**
 * Parse a report file and apply a function on each data line
 * Return FALSE in case of error
 *
 * @param string   $path        Report path (uncompressed)
 * @param callable $function    Function to apply to each report line
 * @param string   $measurement Measurement name
 *
 * @return void|boolean
 */
function parse_report($path, $function, $measurement)
{
    $handle = fopen($path, 'r');

    if ($handle === FALSE) {
        error_log("Can't read report file $path");
        return FALSE;
    }

    $headers = fgetcsv($handle);

    while (($line_data = fgetcsv($handle)) !== FALSE) {
        call_user_func(
            $function,
            array_combine($headers, $line_data),
            $measurement
        );
    }

    fclose($handle);
}


/**
 * Output a report line using InfluxDB line format
 *
 * @param array  $line        Report data of one line
 * @param string $measurement Measurement name
 *
 * @return void
 */
function output_line_influxdb($line, $measurement)
{
    $timestamp = strtotime($line['lineItem/UsageEndDate']);

    $tags = [
        'account_id'     => $line['lineItem/UsageAccountId'],
        'product_code'   => $line['lineItem/ProductCode'],
        'resource_id'    => $line['lineItem/ResourceId'],
        'usage_type'     => $line['lineItem/UsageType'],
        'operation'      => $line['lineItem/Operation'],
        'transfer_type'  => $line['product/transferType'],
        'product_family' => $line['product/productFamily']
    ];

    $fields = [
        'blended_cost'   => $line['lineItem/BlendedCost'],
        'unblended_cost' => $line['lineItem/UnblendedCost'],
        'usage_amount'   => $line['lineItem/UsageAmount'],
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
