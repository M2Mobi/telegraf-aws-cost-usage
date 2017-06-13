<?php

// Temporary file to extract compressed CSV reports to
$TMP_CSV = '/tmp/aws_cost_usage.csv';

// Output format to generate
$OUTPUT_FORMAT = 'influxdb';

// Local path of the bucket containing all the reports
$BUCKET_PATH = '/mnt/s3';

// Prefix of the reports
$REPORT_PREFIX = 'hourly';

// Name of the reports
$REPORT_NAME = 'hourly_report';

// Measurement name
$MEASUREMENT = 'aws_cost_usage';

// Filter the lines to use (header name => value)
$FILTERS = [
    'bill/PayerAccountId'     => '123456789',
    'lineItem/UsageAccountId' => '1357924680'
];

?>
