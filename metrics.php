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
 * Publically accessible endpoint that displays Moodle
 * metrics in the Prometheus data format
 *
 * @package     local_prometheus
 * @copyright   2022 John Maydew <jdmayd@essex.ac.uk>
 * @license     2022 University of Essex
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/locallib.php';

global $DB, $CFG, $SITE;

$authToken = get_config('local_prometheus', 'token');
$tokenAuthEnabled = !empty($authToken);

$token = $tokenAuthEnabled
    ? optional_param('token', '', PARAM_BASE64)
    : required_param('token', PARAM_BASE64);

$timeframe = optional_param('timeframe', 60 * 5, PARAM_INT);

$cutoff = time() - $timeframe;

if($tokenAuthEnabled && $token !== $authToken) {
    http_response_code(403);
    exit;
}

header('Content-Type: text/plain', true);

// Build default labels
$defaultLabels = [];
if(get_config('local_prometheus', 'sitetag'))
    $defaultLabels['site'] = $SITE->shortname;

if(get_config('local_prometheus', 'versiontag')) {
    $defaultLabels['version'] = $CFG->version;
    $defaultLabels['release'] = $CFG->release;
}

foreach(explode(PHP_EOL, get_config('local_prometheus', 'extratags')) as $tagLine) {
    if(!strpos($tagLine, '='))
        continue;

    list($key, $value) = explode('=', $tagLine);
    $key = trim($key);
    $value = trim($value);
    $defaultLabels[$key] = $value;
}

$values = [];


// Grab user statistics
if(get_config('local_prometheus', 'userstatistics')) {
    $currentlyOnline = $DB->count_records_select('user', 'lastaccess > ?', [ $cutoff ]);
    $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_users_online', $currentlyOnline, $defaultLabels, get_string('metric:onlineusers', 'local_prometheus') ];

    $data = $DB->get_records_sql("
SELECT	MAX(usr.id), 
        auth,
        (
            SELECT	COUNT('x')
            FROM	moodle.mdl_user
            WHERE	auth = usr.auth
                AND	deleted = 0
                AND	suspended = 0
        ) AS active,
        (
            SELECT	COUNT('x')
            FROM	moodle.mdl_user
            WHERE	auth = usr.auth
                AND	deleted = 1
                AND	suspended = 0
        ) AS deleted,
        (
            SELECT	COUNT('x')
            FROM	moodle.mdl_user
            WHERE	auth = usr.auth
                AND	deleted = 0
                AND	suspended = 1
        ) AS suspended
FROM	moodle.mdl_user usr
GROUP BY auth");

    foreach($data as $item) {
        $labels = array_merge(
            $defaultLabels,
            [ 'auth' => $item->auth ]
        );

        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_users_active', $item->active, $labels, get_string('metric:activeusers', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_users_deleted', $item->deleted, $labels, get_string('metric:deletedusers', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_users_suspended', $item->suspended, $labels, get_string('metric:suspendedusers', 'local_prometheus') ];
    }

    unset($data);
}

// Grab course and enrolment statistics
if(get_config('local_prometheus', 'coursestatistics')) {

    $data = $DB->get_records_sql("
SELECT	MAX(course.id),
        format,
		theme,
		(
			SELECT	COUNT('x')
			FROM	{course}
			WHERE	format = course.format
				AND	theme = course.theme
				AND visible = 0
		) AS hidden,
		(
			SELECT	COUNT('x')
			FROM	{course}
			WHERE	format = course.format
                AND	theme = course.theme
                AND visible = 1
		) AS visible
FROM	{course} AS course
GROUP BY format, theme");

    foreach($data as $line) {
        $labels = array_merge(
            $defaultLabels,
            [
                'theme' => $line->theme,
                'format' => $line->format
            ]
        );
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_courses_visible', $line->visible, $labels, get_string('metric:coursesvisible', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_courses_hidden', $line->hidden, $labels, get_string('metric:courseshidden', 'local_prometheus') ];
    }
}

// Grab enrolment statistics
if(get_config('local_prometheus', 'enrolstatistics')) {
    $data = $DB->get_records_sql("
SELECT	id,
        enrol,
    (
        SELECT	COUNT('x')
        FROM	{enrol}
        WHERE	enrol = enrol.enrol
            AND	status = 1
    ) AS disabled,
    (
        SELECT	COUNT('x')
        FROM	{enrol}
        WHERE	enrol = enrol.enrol
            AND	status = 0
    ) AS enabled,
    (
        SELECT	COUNT('x')
        FROM	{user_enrolments} user_enrol
            INNER JOIN moodle.mdl_enrol enrol
                ON	enrol.id = user_enrol.enrolid
        WHERE	enrol.enrol = enrol.enrol
            AND	user_enrol.status = 0
    ) AS active_enrolments,
    (
        SELECT	COUNT('x')
        FROM	{user_enrolments} user_enrol
            INNER JOIN moodle.mdl_enrol enrol
                ON	enrol.id = user_enrol.enrolid
        WHERE	enrol.enrol = out.enrol
            AND	user_enrol.status = 1
    ) AS suspended_enrolments
FROM	{enrol} AS enrol
GROUP BY enrol");

    foreach($data as $line) {
        $labels = array_merge(
            $defaultLabels,
            [ 'enrol' => $line->enrol ]
        );

        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_enrolments_enabled', $line->enabled, $labels, get_string('metric:enrolsenabled', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_enrolments_disabled', $line->disabled, $labels, get_string('metric:enrolsdisabled', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_enrolments_active', $line->active_enrolments, $labels, get_string('metric:enrolsactive', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_enrolments_suspended', $line->suspended_enrolments, $labels, get_string('metric:enrolssuspended', 'local_prometheus') ];
    }

    unset($data);
}

// Grab activity module statistics
if(get_config('local_prometheus', 'modulestatistics')) {
    $data = $DB->get_records_sql("
SELECT	id,
        name,
        (
            SELECT	COUNT('x')
            FROM	{course_modules} course_module
            WHERE	course_module.module = module.id
                AND	deletioninprogress = 0
                AND course_module.visible = 1
        ) AS visible,
        (
            SELECT	COUNT('x')
            FROM	{course_modules} course_module
            WHERE	course_module.module = module.id
                AND	deletioninprogress = 0
                AND course_module.visible = 0
        ) AS hidden
FROM	{modules} AS module
GROUP BY module.name, module.id");

    foreach($data as $line) {
        $labels = array_merge(
            $defaultLabels,
            [ 'module' => $line->name ]
        );

        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_modules_visible', $line->visible, $labels, get_string('metric:modulesvisible', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_modules_hidden', $line->hidden, $labels, get_string('metric:moduleshidden', 'local_prometheus') ];
    }
}

// Grab scheduled task statistics
if(get_config('local_prometheus', 'taskstatistics')) {
    $tasks = $DB->get_records_sql("
SELECT	MAX(id),
        component,
        classname,
        COUNT('x') AS runs,
        SUM(result) AS failures
FROM	{task_log}
WHERE   timeend > ?
GROUP BY component, classname",
    [ $cutoff ]);

    foreach($tasks as $task) {
        $labels = array_merge(
            $defaultLabels,
            [
                'component' => $task->component,
                'classname' => $task->classname
            ]
        );

        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_task_runs', $task->runs, $labels, get_string('metric:taskruns', 'local_prometheus') ];
        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_task_failures', $task->failures, $labels, get_string('metric:taskfailures', 'local_prometheus') ];
    }

    unset($tasks);
}

if(get_config('local_prometheus', 'activitystatistics')) {

    $logs = $DB->get_recordset_sql("
SELECT	0,
		component,
		crud,
		edulevel,
		origin,
		COUNT('x') as items
FROM	moodle.mdl_logstore_standard_log
WHERE   timecreated > ?
GROUP BY component, crud, edulevel, origin",
    [ $cutoff ]);

    foreach($logs as $log) {
        $labels = array_merge(
            $defaultLabels,
            [
                'component' => $log->component,
                'crud' => $log->crud,
                'edulevel' => $log->edulevel,
                'origin' => $log->origin
            ]
        );

        $values[] = [ PROMETHEUS_TYPE_GAUGE, 'moodle_log_items', $log->items, $labels, get_string('metric:logitems', 'local_prometheus') ];
    }

}

usort($values, function($a, $b) {
    return strcmp($a[1], $b[1]);
});

foreach($values as $value) {
    echo format_prometheus_lines($value[0], $value[1], $value[2], $value[3], $value[4]);
}