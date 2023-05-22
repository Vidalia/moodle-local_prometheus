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

use coding_exception;

/**
 * Represents a value or dimension of a metric, with one or more labels, value, and timestamp
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metric_value {

    /**
     * @var array Array of name => value labels for this dimension
     */
    protected array $labels;

    /**
     * @var float The value
     */
    protected float $value;

    /**
     * @var int|null Millisecond-precision timestamp
     */
    protected ?int $timestamp;

    /**
     * Constructs a new metric value
     *
     * @param array $labels An array of name => value labels
     * @param float $value The value
     * @param int|null $timestamp A millisecond-precision timestamp, or null to use the current time
     */
    public function __construct(array $labels, float $value, ?int $timestamp = null) {
        self::validate_labels($labels);

        $this->labels = $labels;
        $this->value = $value;
        $this->timestamp = $timestamp;
    }

    /**
     * Label names may contain ASCII letters, numbers, as well as underscores.
     * Label values may contain any unicode characters
     *
     * @param array $labels An array of name => value labels
     */
    final public static function validate_labels(array $labels) {

        foreach ($labels as $name => $value) {
            if (!preg_match("/[a-zA-Z_][a-zA-Z0-9_]*/", $name)) {
                throw new coding_exception('Invalid label name', "'$name' does not match regex");
            }
        }
    }

    /**
     * Outputs the value for a given metric
     *
     * @param metric $metric      The metric this value is for
     * @param array $sharedlabels A list of shared labels that should be prepended
     * @return string
     */
    public function output(metric $metric, array $sharedlabels = []): string {
        $output = $metric->get_name();
        $alllabels = array_merge($sharedlabels, $this->labels);

        if (!empty($alllabels)) {
            // Format the labels as key="value", and join them with a comma.
            $formattedlabels = array_map(
                function(string $name, string $value): string {
                    return "$name=\"$value\"";
                },
                array_keys($alllabels),
                array_values($alllabels)
            );
            $formattedlabels = implode(',', $formattedlabels);

            $output .= "{{$formattedlabels}}";
        }

         $output .= " $this->value";

        // Append the timestamp if we have it.
        if (!is_null($this->timestamp)) {
            $output .= " $this->timestamp";
        }

        return $output;
    }

}
