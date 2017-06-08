<?php

include 'bucket.php';
include 'report.php';
include 'config.php';

$manifest_path = get_current_manifest_path(
    $BUCKET_PATH,
    $REPORT_PREFIX,
    $REPORT_NAME
);

if ($manifest_path === FALSE) {
    exit;
}

$reports_paths = get_reports_paths(
    $BUCKET_PATH,
    $manifest_path
);

if ($reports_paths === FALSE) {
    exit;
}

foreach ($reports_paths as $report_path) {
    extract_gzip_report($report_path, $TMP_CSV);
    parse_report($TMP_CSV, 'output_line_' . $OUTPUT_FORMAT);
};

unlink($TMP_CSV);

?>
