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

}
