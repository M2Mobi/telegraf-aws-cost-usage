# AWS Cost and Usage for Telegraf

This repository contains PHP scripts extracting Amazon Web Services Cost and Usage data
to be feeded into Telegraf


## Usage

```
./extract_report.php -c CONFIG_FILE [-m TARGET_MONTH]

Required option:
    -c   CONFIG_FILE    define the config file to load
                        must contain all variables as in the config_example.php

Optional option:
    -m   TARGET_MONTH   define the report month to extract
                        value can be either 'current' or 'previous'
                        default value is 'current'
```


## Restrictions

- The scripts do NOT download reports from Amazon S3 but expect them to be available locally,
for example through [s3fs](https://github.com/s3fs-fuse/s3fs-fuse)
- Reports are expected to use GZIP compression
- Only [influx output format](https://github.com/influxdata/telegraf/blob/master/docs/DATA_FORMATS_INPUT.md#influx) is supported
- Only a subset of each report line is sent to Telegraf  
  The choice of those fields was made towards the creation of metrics regarding data transfer and overall price per account



## Reports overview

The AWS Cost and Usage reports can be managed from the
[Amazon console](https://console.aws.amazon.com/billing/home#/reports)  
All reports are stored in an Amazon S3 bucket.  
Each individual report contains the full data for the current billing month at the time of generation.  
The structure of the bucket folders and files, and the format of the CSV files are defined in the
[Amazon docs](http://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/billing-reports.html#enhanced-organization)

Extra notes:
- Amazon seem to create a new report twice per day (~6am and ~6pm)
- At the end of a month, the final days' data is missing from the report.  
They seem to be added after ~5 days in the following month.


## Recommended setup

Given the way the reports are generated by Amazon, it is recommended to feed the data to Telegraf
through the [tail input plugin](https://github.com/influxdata/telegraf/tree/master/plugins/inputs/tail)
and 2 cronjobs.  
One twice daily import for the current billing month.  
One monthly import for the previous billing month (to fix the last few days).

Telegraf tail input config
```
[[inputs.tail]]
  files = [ "/var/tmp/aws_cost_usage.out" ]
  data_format = "influx"
```

Cronjobs config
```
0 7,19 * * * extract_report.php -c myconfig.php -m current > /var/tmp/aws_cost_usage.out 2>> /var/log/aws_cost_usage.log
0 3 7 * * extract_report.php -c myconfig.php -m previous > /var/tmp/aws_cost_usage.out 2>> /var/log/aws_cost_usage.log
```


## Similar project

- [awsbill2graphite](https://github.com/danslimmon/awsbill2graphite)  
Using Python  
Download reports from Amazon S3  
Send different metrics directly to Graphite


## TODO

- Use compression defined in Manifest file to decompress reports
- add `-h` cli argument to print usage
- validate that the config file contains all required variables
