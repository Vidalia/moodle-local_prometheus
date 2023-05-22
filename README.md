# Moodle Prometheus reporting endpoint
A local plugin that presents an endpoint for Prometheus metric gathering.
Can be used either in Prometheus or as an InfluxDB v2 scraper

## Developing
Plugins can add their own metrics to the output by adding their own `plugin_name_prometheus_get_metrics(int $window)` function
to lib.php. This function **must** return either one or more `\local_prometheus\metric` objects, or an empty array.
Plugins **should** give users the option to toggle metric gathering for that plugin on or off.

The `$window` parameter is a unix timestamp and is used for determining the cutoff for a 'current' value. For example to determine 
the number of currently online users we can only look at the last access timestamp. In this case, `$window` would mean "treat users 
active since this timestamp as currently online". 

Example implementation in lib.php:
```php

/**
 * Fetch metrics for the plugin
 *
 * @param int $window
 * @return metric[] 
 */
mod_example_prometheus_get_metrics(int $window): array {
    $metric = new metric(
        'moodle_mod_example_foo',
        metric::TYPE_GAUGE,
        'optional HELP text, can be omitted'
    );
    
    $metric->add_value(new metric_value(
        [ 'label' => 'foo' ],
        12
    ));
    
    return [ $metric ];
}
```

## Installation
The plugin requires Moodle 4.0 or later.
There's no special installation steps or instructions, just install it as you would any other plugin

### Install from Moodle.org
- Download .zip file from https://moodle.org/plugins/local_prometheus
- Navigate to `/moodle/root/local`
- Extract the .zip to the current directory
- Go to your Moodle admin control panel, or `php /moodle/root/admin/cli/upgrade.php`

### Install from git
- Navigate to your Moodle root folder
- `git clone https://github.com/Vidalia/moodle-local_prometheus.git local/prometheus`
- Make sure that user:group ownership and permissions are correct
- Go to your Moodle admin control panel, or `php /moodle/root/admin/cli/upgrade.php`

### Install from .zip
- Download .zip file from GitHub
- Navigate to `/moodle/root/local/prometheus`
- Extract the .zip to the current directory
- Rename the `moodle-local_prometheus-master` directory to `prometheus`
- Go to your Moodle admin control panel, or `php /moodle/root/admin/cli/upgrade.php`
