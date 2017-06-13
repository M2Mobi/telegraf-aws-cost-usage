<?php
/**
 * This file contains functions to output a report line using the influxdb format
 */

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
        'item_id'           => $line['identity/LineItemId'],
        'account_id'        => $line['lineItem/UsageAccountId'],
        'product_code'      => $line['lineItem/ProductCode'],
        'resource_id'       => $line['lineItem/ResourceId'],
        'usage_type'        => $line['lineItem/UsageType'],
        'operation'         => $line['lineItem/Operation'],
        'availability_zone' => $line['lineItem/AvailabilityZone']
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

    foreach ($tags as $key => $value) {
        if ($value !== '') {
            $result .= ",$key=" . str_replace(' ', '\ ', $value);
        }
    }

    $result .= ' ';

    $f = [];
    foreach ($fields as $key => $value) {
        if ($value !== '') {
            $f[] = "$key=$value";
        }
    }
    $result .= implode(',', $f);

    $result .= ' ';
    $result .= $timestamp . '000000000'; // nanoseconds
    $result .= "\n";

    return $result;
}

?>
