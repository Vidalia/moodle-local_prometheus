<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

const PROMETHEUS_TYPE_COUNTER = "counter";
const PROMETHEUS_TYPE_GAUGE = "gauge";
const PROMETHEUS_TYPE_HISTOGRAM = "histogram";
const PROMETHEUS_TYPE_SUMMARY = "summary";
const PROMETHEUS_TYPE_UNTYPED = "untyped";

/**
 * Formats a single metric into the Prometheus exposition format
 * @param string $type Metric type (See PROMETHEUS_TYPE_* consts)
 * @param string $metricName The metric's name
 * @param mixed $value Metric's value
 * @param array $labels An array of key/value pairs to include as labels
 * @param string $help Metric description
 * @param int|null $timestamp Timestamp (optional)
 * @return string The formatted metric
 */
function format_prometheus_lines(string $type, string $metricName, $value, array $labels = [], string $help = '', int $timestamp = null): string {

    // HELP and TYPE metrics should only be output once per metric
    // TODO: This doesn't work for the histogram or summary types, but we don't use them anyway
    static $outputtedMetrics = [];

    // Format labels
    $labelString = "";
    if(!empty($labels)) {
        $items = [];
        array_walk($labels, function($value, $name) use (&$items) {
            $value = addslashes($value);
            $items[] = "$name=\"$value\"";
        });

        $labelString = '{' . implode(',', $items) . '}';
    }
    $valueString = " $value";

    // Append timestamp, if supplied
    $timestampString = is_int($timestamp)
        ? " $timestamp"
        : '';

    $return = "";

    // Prepend # HELP comment
    if(!in_array($metricName, $outputtedMetrics)) {
        if(!empty($outputtedMetrics)) {
            $return .= "\n\n";
        }
        if(!empty($help)) {
            $return .= "# HELP $metricName $help\n";
        }

        // Add # TYPE comment
        $return .= "# TYPE $metricName $type\n";

        $outputtedMetrics[] = $metricName;
    }

    // Append all the different parts together
    $return .= implode('', [
        $metricName, $labelString, $valueString, $timestampString, "\n"
    ]);

    return $return;
}