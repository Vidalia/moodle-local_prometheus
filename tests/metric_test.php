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

use basic_testcase;
use coding_exception;

/**
 * Tests for {@see metric}
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class metric_test extends basic_testcase {

    /**
     * Tests name validation. Metric names may only contain ASCII characters, underscores
     * and colons, but may not start with a number.
     *
     * @dataProvider name_validation_provider
     * @covers \local_prometheus\metric::validate_name
     *
     * @param string $name
     * @param bool $isvalid
     * @return void
     */
    public function test_name_validation(string $name, bool $isvalid): void {

        if ($isvalid === false) {
            $this->expectException(coding_exception::class);
        }
        metric::validate_name($name);

    }

    /**
     * Data provider for {@see test_name_validation}
     *
     * @return array
     */
    public function name_validation_provider(): array {
        return [
            'alphabetical only' => [ 'name' => 'metricname', 'isvalid' => true ],
            'underscores' => [ 'name' => 'mod_metric_name', 'isvalid' => true ],
            'with numbers' => [ 'name' => 'mod_metric_13', 'isvalid' => true ],
            'has colon' => [ 'name' => 'mod:metric_name', 'isvalid' => true ],
            'mixed case' => [ 'name' => 'MOD_METRIC_name', 'isvalid' => true ],
            'starts with number' => [ 'name' => '14_metric', 'isvalid' => false ],
            'has non-alphabetical chars' => [ 'name' => 'metric@name', 'isvalid' => false ],
            'has utf-8 chars' => [ 'name' => 'mod_metric_🙂', 'isvalid' => false ],
        ];
    }

    /**
     * Verifies that a metric can be output in the prometheus format
     *
     * @covers       \local_prometheus\metric::output
     * @dataProvider metric_output_provider
     *
     * @param string $name Metric name
     * @param string $type Metric type
     * @param string $help Optional help text
     * @param metric_value[] $values List of values
     * @param array $labels Shared labels
     * @param string $expected Expected output
     * @return void
     */
    public function test_metric_output(string $name,
                                       string $type,
                                       string $help,
                                       array  $values,
                                       array  $labels,
                                       string $expected): void {

        $metric = new metric($name, $type, $help);
        foreach ($values as $value) {
            $metric->add_value($value);
        }

        // Line endings are significant. We want to be able to see the line endings in the dataprovider,
        // but we don't want to faff about mixing ending types in the same file.
        $expected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($expected, $metric->output($labels));
    }

    /**
     * Data provider for {@see test_metric_output}
     *
     * @return array{string: array}
     */
    public function metric_output_provider(): array {
        return [
            'simple gauge' => [
                'name' => 'test_simple_gauge', 'type' => metric::TYPE_GAUGE, 'help' => '',
                'values' => [ new metric_value([], 1) ],
                'labels' => [],
                'expected' => <<<PROMETHEUS
# TYPE test_simple_gauge gauge
test_simple_gauge 1
PROMETHEUS,
            ],
            'gauge, with help text' => [
                'name' => 'test_simple_gauge_withhelp', 'type' => metric::TYPE_GAUGE,
                'help' => 'This metric has a description',
                'values' => [ new metric_value([], 1) ],
                'labels' => [],
                'expected' => <<<PROMETHEUS
# HELP test_simple_gauge_withhelp This metric has a description
# TYPE test_simple_gauge_withhelp gauge
test_simple_gauge_withhelp 1
PROMETHEUS,
            ],
            'gauge, with shared labels' => [
                'name' => 'test_gauge_withlabel', 'type' => metric::TYPE_GAUGE, 'help' => '',
                'values' => [ new metric_value([], 1) ],
                'labels' => [ 'label' => 'value' ],
                'expected' => <<<PROMETHEUS
# TYPE test_gauge_withlabel gauge
test_gauge_withlabel{label="value"} 1
PROMETHEUS,
            ],
            'gauge with multiple values' => [
                'name' => 'multi_gauge', 'type' => metric::TYPE_GAUGE, 'help' => '',
                'values' => [
                    new metric_value(['item' => 'one'], 1),
                    new metric_value(['item' => 'two'], 2),
                ],
                'labels' => [],
                'expected' => <<<PROMETHEUS
# TYPE multi_gauge gauge
multi_gauge{item="one"} 1
multi_gauge{item="two"} 2
PROMETHEUS,
            ],
            'counter with everything' => [
                'name' => 'multi_counter', 'type' => metric::TYPE_COUNTER,
                'help' => 'Here\'s a description',
                'values' => [
                    new metric_value(['item' => 'one'], 1),
                    new metric_value(['item' => 'two'], 2),
                    new metric_value(['item' => 'three', 'zoo' => 'closed' ], 3),
                ],
                'labels' => [ 'shared' => 'label' ],
                'expected' => <<<PROMETHEUS
# HELP multi_counter Here's a description
# TYPE multi_counter counter
multi_counter{item="one",shared="label"} 1
multi_counter{item="two",shared="label"} 2
multi_counter{item="three",shared="label",zoo="closed"} 3
PROMETHEUS,
            ],
        ];
    }

}
