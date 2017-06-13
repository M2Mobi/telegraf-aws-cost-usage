<?php

include 'lib/cli.php';

if (! get_cli_options($config_file, $target_month)) {
    exit(1);
}

include $config_file;
include 'lib/report.php';
include "lib/${OUTPUT_FORMAT}.php";

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
