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
 * Prometheus reporting plugin settings and presets
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $ADMIN;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_prometheus', get_string('pluginname', 'local_prometheus'));

    $existingtoken = get_config('local_prometheus', 'token');
    if (empty($existingtoken)) {
        $existingtoken = base64_encode(md5(mt_rand()));
    }

    $tokenurl = new moodle_url('/local/prometheus/metrics.php',
        [ 'token' => $existingtoken ]
    );

    $timeframeurl = new moodle_url($tokenurl,
        [ 'timeframe' => 3600 ]
    );

    $params = [
        'tokenurl' => $tokenurl->out(),
        'timeframeurl' => $timeframeurl->out()
    ];
    $settings->add(new admin_setting_description('local_prometheus_usage', '',
        get_string('usage', 'local_prometheus', $params)
    ));

    // Authentication options.
    $settings->Add(new admin_setting_heading('local_prometheus_auth',
        get_string('heading:auth', 'local_prometheus'),
        get_string('heading:auth:information', 'local_prometheus')
    ));

    $tokeninput = new admin_setting_configtext('local_prometheus/token',
        get_string('token', 'local_prometheus'),
        get_string('token:description', 'local_prometheus', base64_encode(md5(mt_rand()))),
        $existingtoken
    );
    $settings->add($tokeninput);

    // Output option settings.
    $settings->add(new admin_setting_heading('local_prometheus_outputs',
        get_string('heading:outputs', 'local_prometheus'),
        get_string('heading:outputs:information', 'local_prometheus')
    ));

    $settings->add(new admin_setting_configtextarea('local_prometheus/extratags',
        get_string('extratags', 'local_prometheus'),
        get_string('extratags:description', 'local_prometheus'),
        ''
    ));

    $checkboxes = [
        'sitetag' => true,
        'versiontag' => false,

        'userstatistics' => true,
        'coursestatistics' => true,
        'modulestatistics' => true,
        'taskstatistics' => true,
        'activitystatistics' => true
    ];

    foreach ($checkboxes as $key => $default) {
        $settings->add(new admin_setting_configcheckbox("local_prometheus/$key",
            get_string($key, 'local_prometheus'),
            get_string("$key:description", 'local_prometheus'),
            $default ? '1' : '0'
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
