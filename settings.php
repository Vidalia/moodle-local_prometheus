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
 * @package local_prometheus
 * @copyright 2022 John Maydew <jdmayd@essex.ac.uk>
 * @license 2022 University of Essex
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

if($hassiteconfig) {

    $settings = new admin_settingpage('local_prometheus', get_string('pluginname', 'local_prometheus'));

    $existingToken = get_config('local_prometheus', 'token');
    if(empty($existingToken))
        $existingToken = base64_encode(md5(mt_rand()));

    $tokenUrl = new moodle_url('/local/prometheus/metrics.php',
        [ 'token' => $existingToken ]
    );

    $timeframeUrl = new moodle_url($tokenUrl,
        [ 'timeframe' => 3600 ]
    );

    $params = [
        'tokenurl' => $tokenUrl->out(),
        'timeframeurl' => $timeframeUrl->out()
    ];
    $settings->add(new admin_setting_description('local_prometheus_usage', '',
        get_string('usage', 'local_prometheus', $params)
    ));

    // Authentication options
    $settings->Add(new admin_setting_heading('local_prometheus_auth',
        get_string('heading:auth', 'local_prometheus'),
        get_string('heading:auth:information', 'local_prometheus')
    ));

    $tokenInput = new admin_setting_configtext('local_prometheus/token',
        get_string('token', 'local_prometheus'),
        get_string('token:description', 'local_prometheus', base64_encode(md5(mt_rand()))),
        $existingToken
    );
    $settings->add($tokenInput);

    // Output option settings
    $settings->add(new admin_setting_heading('local_prometheus_outputs',
        get_string('heading:outputs', 'local_prometheus'),
        get_string('heading:outputs:information', 'local_prometheus')
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

    foreach($checkboxes as $key => $default) {
        $settings->add(new admin_setting_configcheckbox("local_prometheus/$key",
            get_string($key, 'local_prometheus'),
            get_string("$key:description", 'local_prometheus'),
            $default ? '1' : '0'
        ));
    }

    $settings->add(new admin_setting_configtextarea('local_prometheus/extratags',
        get_string('extratags', 'local_prometheus'),
        get_string('extratags:description', 'local_prometheus'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}