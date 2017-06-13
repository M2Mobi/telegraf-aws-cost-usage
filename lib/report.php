<?php
/**
 * This file contains functions to retrieve and parse the reports
 */

/**
 * Return the file path of a manifest from a billing month
 * Return FALSE in case of error or if the file cannot be found
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $report_prefix Prefix assigned to the report
 * @param string $report_name   Name assigned to the report
 * @param string $billing_year  Billing year in the format YYYY
 * @param string $billing_month Billing month in the format MM
 *
 * @return string|boolean
 */
function get_manifest_path($bucket_path, $report_prefix, $report_name, $billing_year, $billing_month)
{
    $month_folder_name_pattern = $billing_year . $billing_month . '*';
    $manifest_filename         = $report_name . '-Manifest.json';

    $manifest_path_pattern = implode(
        DIRECTORY_SEPARATOR,
        [
            $bucket_path,
            $report_prefix,
            $report_name,
            $month_folder_name_pattern,
            $manifest_filename
        ]
    );

    $search_result = glob($manifest_path_pattern);

    if (empty($search_result)) {
        error_log("No manifest file found matching pattern $manifest_path_pattern");
        return FALSE;
    }

    return $search_result[0];
}

/**
 * Return the manifest file path for the current billing month
 * Return FALSE in case of error or if the file cannot be found
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $report_prefix Prefix assigned to the report
 * @param string $report_name   Name assigned to the report
 *
 * @return string|boolean
 */
function get_current_manifest_path($bucket_path, $report_prefix, $report_name)
{
    $current_year  = date('Y');
    $current_month = date('m');

    return get_manifest_path(
        $bucket_path,
        $report_prefix,
        $report_name,
        $current_year,
        $current_month
    );
}

/**
 * Return the manifest file path for the previous billing month
 * Return FALSE in case of error or if the file cannot be found
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $report_prefix Prefix assigned to the report
 * @param string $report_name   Name assigned to the report
 *
 * @return string|boolean
 */
function get_previous_manifest_path($bucket_path, $report_prefix, $report_name)
{
    $previous_month_day   = strtotime('first day of previous month');
    $previous_month_year  = date('Y', $previous_month_day);
    $previous_month_month = date('m', $previous_month_day);

    return get_manifest_path(
        $bucket_path,
        $report_prefix,
        $report_name,
        $previous_month_year,
        $previous_month_month
    );
}

/**
 * Return all the reports file paths defined in the manifest file
 * Paths point to compressed files
 * Return FALSE in case of error
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $manifest_path Manifest file path
 *
 * @return array|boolean
 */
function get_reports_paths($bucket_path, $manifest_path)
{
    $manifest_content = file_get_contents($manifest_path);

    if ($manifest_content === FALSE) {
        error_log("Can't read manifest file $manifest_path");
        return FALSE;
    }

    $manifest_data = json_decode($manifest_content, TRUE);

    if (is_null($manifest_data)) {
        error_log("Can't decode manifest file $manifest_path");
        return FALSE;
    }

    if (! isset($manifest_data['reportKeys'])) {
        error_log("Missing reportKeys in manifest file $manifest_path");
        return FALSE;
    }

    $reports_paths = array_map(
        function ($relative_path) use ($bucket_path) {
            return $bucket_path . DIRECTORY_SEPARATOR . $relative_path;
        },
        $manifest_data['reportKeys']
    );

    return $reports_paths;
}

/**
 * Extract a gzip report to a target file
 * Return FALSE in case of error
 *
 * @param string $report_path Path of the report to extract
 * @param string $target_path Path of the file to extract to
 *
 * @return void|boolean
 */
function extract_gzip_report($report_path, $target_path)
{
    $report_handle = gzopen($report_path, 'rb');

    if ($report_handle === FALSE) {
        error_log("Can't open report file $report_path for reading");
        return FALSE;
    }

    $target_handle = fopen($target_path, 'w');

    if ($target_handle === FALSE) {
        error_log("Can't open target file $target_path for writing");
        return FALSE;
    }

    while (! gzeof($report_handle)) {
        $write_result = fwrite($target_handle, gzread($report_handle, 4096));

        if ($write_result === FALSE) {
            gzclose($report_handle);
            fclose($target_handle);
            error_log("Failed to write some data to $target_path");
            return FALSE;
        }
    }

    gzclose($report_handle);
    fclose($target_handle);
}

/**
 * Parse a report file and apply a function on each data line
 * Return FALSE in case of error
 *
 * @param string   $path        Report path (uncompressed)
 * @param callable $function    Function to apply to each report line
 * @param string   $measurement Measurement name
 * @param array    $filters     Filters to select the lines to use
 *
 * @return void|boolean
 */
function parse_report($path, $function, $measurement, $filters = [])
{
    $handle = fopen($path, 'r');

    if ($handle === FALSE) {
        error_log("Can't read report file $path");
        return FALSE;
    }

    $headers = fgetcsv($handle);

    while (($line_data = fgetcsv($handle)) !== FALSE) {
        $line = array_combine($headers, $line_data);

        if (! check_line_filters($line, $filters)) {
            continue;
        }

        call_user_func($function, $line, $measurement);
    }

    fclose($handle);
}

/**
 * Check a report line against the filters
 * Return TRUE if the line should be used, FALSE otherwise
 *
 * @param array $line    Report data of one line
 * @param array $filters Filters definition (header name => value)
 *
 * @return boolean
 */
function check_line_filters($line, $filters)
{
    foreach ($filters as $key => $value) {
        if ($line[$key] != $value) {
            return FALSE;
        }
    }

    return TRUE;
}

?>
