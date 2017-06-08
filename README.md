# AWS Cost and Usage for Telegraf

This repository contains scripts extracting Amazon Web Services Cost and Usage data
to be feeded into Telegraf

## Reports overview

The AWS Cost and Usage reports can be managed from the
[Amazon console](https://console.aws.amazon.com/billing/home#/reports)  
All reports are stored in an Amazon S3 bucket.  
Each individual report contains the full data for the current billing month at the time of generation.  
The structure of the bucket folders and files, and the format of the CSV files are defined in the
[Amazon docs](http://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/billing-reports.html#enhanced-organization)

Extra notes:
- Amazon seem to create a new report twice per day
- At the end of a month, the final days' data is missing from the report.  
They seem to be added after ~5 days in the following month.

## Restrictions
The scripts do NOT download reports from Amazon S3 but expect them to be available locally,
for example through [s3fs](https://github.com/s3fs-fuse/s3fs-fuse)

Reports are expected to use GZIP compression

## TODO

- Use compression defined in Manifest file to decompress reports
