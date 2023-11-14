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

namespace local_prometheus;

use dml_exception;
use Exception;
use moodle_exception;

/**
 * Gathers metrics for the current Moodle site
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gatherer {

    /**
     * @var object Plugin configuration object
     */
    protected object $config;

    /**
     * Constructs a new metric gatherer
     *
     * @throws dml_exception
     */
    public function __construct() {
        $this->config = get_config('local_prometheus');
    }

    /**
     * Gather metrics from all plugins that support it.
     * We dogfood the prometheus_get_metrics hook in our own lib.php file
     *
     * @param int $window The window for fetching 'recent' or 'current' metrics
     * @return metric[] Metrics
     */
    final protected function get_metrics(int $window): array {
        $metrics = [];
        $pluginsfunction = get_plugins_with_function('prometheus_get_metrics');

        foreach ($pluginsfunction as $plugins) {
            foreach ($plugins as $pluginfunction) {
                try {
                    $metrics = array_merge($metrics, $pluginfunction($window));
                } catch (Exception $exception) {
                    debugging($exception, DEBUG_NORMAL, $exception->getTrace());
                }
            }
        }

        return $metrics;
    }

    /**
     * Fetches shared labels that should be merged into all metrics
     *
     * @return array key => value pairs of shared labels
     */
    final protected function get_shared_labels(): array {
        global $SITE, $CFG;
        $labels = [];

        if ($this->config->sitetag) {
            $labels['site'] = $SITE->shortname;
        }

        if ($this->config->versiontag) {
            $labels['version'] = $CFG->version;
            $labels['release'] = $CFG->release;
        }

        $taglines = explode(PHP_EOL, $this->config->extratags);
        foreach ($taglines as $tagline) {

            // Lines must be key=value separated, so ignore any lines without an equals.
            if (!strpos($tagline, '=')) {
                continue;
            }

            // Explode the key and value. Limit to two items, so you can use an equals in the value if you really want.
            list($key, $value) = explode('=', $tagline, 2);
            $key = trim($key);
            $value = trim($value);
            $labels[$key] = $value;
        }

        return $labels;
    }

    /**
     * Formats metrics to be output as a string
     *
     * @param int $window
     * @return string
     */
    public function output(int $window = 0): string {
        $sharedlabels = $this->get_shared_labels();

        $metrics = $this->get_metrics(time() - $window);

        return implode(PHP_EOL . PHP_EOL . PHP_EOL,
            // Filter the results of array_map so if any metric fails to output we ignore it in the output.
            array_filter(
                array_map(
                    // Try and output each metric, but on error log the exception and continue so metric
                    // collection isn't interrupted.
                    function (metric $metric) use ($sharedlabels): string {
                        try {
                            return $metric->output($sharedlabels);
                        } catch (moodle_exception $exception) {
                            debugging($exception, DEBUG_NORMAL, $exception->getTrace());
                        }

                        return false;
                    },
                    $metrics
                )
            )
        ) . PHP_EOL;
    }
}
