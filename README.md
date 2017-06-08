# AWS Cost and Usage for Telegraf

This repository contains scripts extracting Amazon Web Services Cost and Usage data
to be feeded into Telegraf

## Reports

The AWS Cost and Usage reports are stored in an Amazon S3 bucket.
The scripts do NOT download reports from there but expect them to be available locally,
for example through [s3fs](https://github.com/s3fs-fuse/s3fs-fuse)

Reports are expected to use GZIP compression

## TODO

- Use compression defined in Manifest file to decompress reports
