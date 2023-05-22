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

/**
 * Shared library functions
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_prometheus\metric;

defined('MOODLE_INTERNAL') || die();

require_once("locallib.php");

/**
 * Grab a default set of metrics
 *
 * @param int $window How far back from the current time to look
 * @return metric[]
 * @throws dml_exception
 */
function local_prometheus_prometheus_get_metrics(int $window): array {
    $config = get_config('local_prometheus');

    $metrics = [];

    if ($config->userstatistics) {
        $metrics[] = local_prometheus_get_userstatistics($window);
    }

    if ($config->coursestatistics) {
        $metrics[] = local_prometheus_get_coursestatistics($window);
        $metrics[] = local_prometheus_get_enrolstatistics($window);
    }

    if ($config->modulestatistics) {
        $metrics[] = local_prometheus_get_modulestatistics($window);
    }

    if ($config->taskstatistics) {
        $metrics[] = local_prometheus_get_taskstatistics($window);
    }

    return array_merge(...$metrics);
}
