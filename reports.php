<?php

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
function get_last_manifest_path($bucket_path, $report_prefix, $report_name) {
    $monthly_folders_path = implode(
        DIRECTORY_SEPARATOR,
        [ $bucket_path, $report_prefix, $report_name ]
    );

    $monthly_folders = scandir($monthly_folders_path, SCANDIR_SORT_DESCENDING);

    if (empty($monthly_folders)) {
        return FALSE;
    }

    $last_month_folder = $monthly_folders[0];
    $manifest_filename = $report_name . '-Manifest.json';

    $last_manifest_path = implode(
        DIRECTORY_SEPARATOR,
        [ $monthly_folders_path , $last_month_folder, $manifest_filename ]
    );

    if (! file_exists($last_manifest_path)) {
        return FALSE;
    }

    return $last_manifest_path;
}

/**
 * Return all the reports file paths defined in the manifest file
 * Return FALSE in case of error
 *
 * @param string $bucket_path   Folder path containing all the reports
 * @param string $manifest_path Manifest file path
 *
 * @return array|boolean
 */
function get_reports_paths($bucket_path, $manifest_path)
{
    $manifest_data = json_decode(
        file_get_contents($manifest_path),
        TRUE
    );

    if (is_null($manifest_data)) {
        return FALSE;
    }

    if (! isset($manifest_data['reportKeys'])) {
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

?>
