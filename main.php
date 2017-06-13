<?php

$options = getopt('c:m:');

if (! isset($options['c'])) {
    error_log('Missing config file argument "-c file_path"');
    exit(1);
}

$config_file = $options['c'];

if (! file_exists($config_file)) {
    error_log("Invalid config file path $config_file");
    exit(1);
}

$target_month = 'current';
if (isset($options['m'])) {
    $target_month = $options['m'];
    if (! in_array($target_month, [ 'current', 'previous' ])) {
        error_log("Invalid target month $target_month, must be either 'current' or 'previous'");
        exit(1);
    }
}

include $config_file;

include 'lib/bucket.php';
include 'lib/report.php';

$manifest_path = call_user_func(
    "get_${target_month}_manifest_path",
    $BUCKET_PATH,
    $REPORT_PREFIX,
    $REPORT_NAME
);

if ($manifest_path === FALSE) {
    exit(1);
}

$reports_paths = get_reports_paths(
    $BUCKET_PATH,
    $manifest_path
);

if ($reports_paths === FALSE) {
    exit(1);
}

foreach ($reports_paths as $report_path) {
    extract_gzip_report($report_path, $TMP_CSV);
    parse_report(
        $TMP_CSV,
        'output_line_' . $OUTPUT_FORMAT,
        $MEASUREMENT,
        $FILTERS
    );
};

unlink($TMP_CSV);

?>
