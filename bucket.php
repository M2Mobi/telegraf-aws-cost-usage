<?php
/**
 * This file contains functions to retrieve reports files from a local bucket
 */

/**
 * Return the file path of the last manifest
 * Return FALSE in case of error or if the file cannot be found
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $report_prefix Prefix assigned to the report
 * @param string $report_name   Name assigned to the report
 *
 * @return string|boolean
 */
function get_last_manifest_path($bucket_path, $report_prefix, $report_name)
{
    $monthly_folders_path = implode(
        DIRECTORY_SEPARATOR,
        [ $bucket_path, $report_prefix, $report_name ]
    );

    $monthly_folders = scandir($monthly_folders_path, SCANDIR_SORT_DESCENDING);

    if (empty($monthly_folders)) {
        error_log("Can't find monthly folders in $monthly_folders_path");
        return FALSE;
    }

    $last_month_folder = $monthly_folders[0];
    $manifest_filename = $report_name . '-Manifest.json';

    $last_manifest_path = implode(
        DIRECTORY_SEPARATOR,
        [ $monthly_folders_path , $last_month_folder, $manifest_filename ]
    );

    if (! file_exists($last_manifest_path)) {
        error_log("No manifest file found in $last_manifest_path");
        return FALSE;
    }

    return $last_manifest_path;
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

?>
