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

use advanced_testcase;
use dml_exception;
use Exception;
use Throwable;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/../classes/metric.php");
require_once(__DIR__ . "/../classes/metric_value.php");

/**
 * Tests for {@see \local_prometheus_get_userstatistics}
 *
 * @covers      \local_prometheus_get_userstatistics
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class userstatistics_test extends advanced_testcase {

    /**
     * Test the active user reporting
     *
     * @dataProvider active_users_count_provider
     *
     * @param array[] $users
     * @param int $window
     * @param int $expected
     * @return void
     * @throws dml_exception
     */
    public function test_active_users_count(array $users, int $window, int $expected): void {
        global $DB;
        require_once(__DIR__."/../locallib.php");

        $this->resetAfterTest();

        foreach ($users as $user) {
            $newuser = $this->getDataGenerator()->create_user($user);
            $user['id'] = $newuser->id;

            $DB->update_record('user', (object) $user);
        }

        [ $currentlyonline ] = local_prometheus_get_userstatistics(time() - $window);

        $values = $currentlyonline->get_values();
        $this->assertCount(1, $values);

        $online = reset($values);
        $this->assertEquals($expected, (int) $online->get_value());
    }

    /**
     * Data provider for {@see test_active_users_count}
     *
     * @return array
     */
    public function active_users_count_provider(): array {
        return [
            'one user, recent login' => [
                'users' => [ [ 'lastaccess' => time() ] ],
                'window' => 5 * MINSECS,
                'expected' => 1,
            ],
            'one user, old login' => [
                'users' => [ [ 'lastaccess' => time() - HOURSECS ] ],
                'window' => 5 * MINSECS,
                'expected' => 0,
            ],
            'one user, old login, bigger window' => [
                'users' => [ [ 'lastaccess' => time() - HOURSECS ] ],
                'window' => 5 * HOURSECS,
                'expected' => 1,
            ],
            'one user, close login' => [
                'users' => [ [ 'lastaccess' => time() - (6 * MINSECS) ] ],
                'window' => 5 * MINSECS,
                'expected' => 0,
            ],
            'two users, both recent' => [
                'users' => [ [ 'lastaccess' => time() ], [ 'lastaccess' => time() - MINSECS ] ],
                'window' => 5 * MINSECS,
                'expected' => 2,
            ],
            'two users, one old' => [
                'users' => [ [ 'lastaccess' => time() ], [ 'lastaccess' => time() - HOURSECS ] ],
                'window' => 5 * MINSECS,
                'expected' => 1,
            ],
        ];
    }

}
