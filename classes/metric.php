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
use Iterator;
use moodle_exception;

/**
 * Represents a metric with one or more dimensions.
 * A Metric is a general feature of a system that is measured.
 *
 * @package     local_prometheus
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metric implements Iterator {

    const TYPE_COUNTER = "counter";
    const TYPE_GAUGE = "gauge";
    const TYPE_HISTOGRAM = "histogram";
    const TYPE_SUMMARY = "summary";
    const TYPE_UNTYPED = "untyped";

    /**
     * @var string Metric name
     */
    protected string $name;

    /**
     * @var string Metric type
     */
    protected string $type;

    /**
     * @var string Metric description
     */
    protected string $help;

    /**
     * @var metric_value[] List of dimensions
     */
    protected array $dimensions;

    /**
     * @var int Array index pointer
     */
    private int $pointer = 0;

    /**
     * Constructs a new metric
     *
     * @param string $name The metrics name
     * @param string $type The metrics data type. See local_prometheus\metric::TYPE_
     * @param string $help Metric description or #Help text
     */
    public function __construct(string $name, string $type, string $help = '') {
        self::validate_name($name);

        $this->name = $name;
        $this->type = $type;
        $this->help = $help;
        $this->dimensions = [];
    }

    /**
     * Metric names may contain ASCII letters and digits, as well as underscores and colons
     *
     * @param string $name
     * @throws coding_exception
     * @return void
     */
    final public static function validate_name(string $name) {
        if (preg_match("/[a-zA-Z_:][a-zA-Z0-9_:]*/", $name)) {
            return;
        }

        throw new coding_exception('Invalid metric name');
    }

    /**
     * Get this metrics name
     *
     * @return string
     */
    final public function get_name(): string {
        return $this->name;
    }

    /**
     * Get this metric's type
     *
     * @return string
     */
    final public function get_type(): string {
        return $this->type;
    }

    /**
     * Adds a new dimension to the metric
     *
     * @param metric_value $dimension
     * @return void
     */
    final public function add_value(metric_value $dimension) {
        $this->dimensions[] = $dimension;
    }

    /**
     * Formats the metric to be output as a string
     *
     * @param array $sharedlabels List of shared labels for all values
     * @return string
     * @throws moodle_exception
     */
    public function output(array $sharedlabels = []): string {

        if (empty($this->dimensions)) {
            return '';
        }

        $lines = [];

        if (!empty($this->help)) {
            $lines[] = "# HELP $this->name $this->help";
        }
        $lines[] = "# TYPE $this->name $this->type";

        foreach ($this->dimensions as $dimension) {
            $lines[] = $dimension->output($this, $sharedlabels);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return metric_value
     */
    public function current(): metric_value {
        return $this->dimensions[$this->pointer];
    }

    /**
     * @return void
     */
    public function next() {
        $this->pointer++;
    }

    /**
     * @return int
     */
    public function key(): int {
        return $this->pointer;
    }

    /**
     * @return bool
     */
    public function valid(): bool {
        return $this->pointer < count($this->dimensions);
    }

    /**
     * @return void
     */
    public function rewind() {
        $this->pointer = 0;
    }
}
