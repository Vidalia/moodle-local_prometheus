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

declare(strict_types=1);

use Behat\Mink\Exception\ExpectationException;

/**
 * Custom behat steps
 *
 * @package     local_prometheus
 * @copyright   2024 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_local_prometheus extends behat_base {

    /**
     * Checks if the given text appears anywhere on the page by doing substring match.
     * The "I should see {text}" step uses xpath element selectors, which don't work with the plaintext
     * output of metrics.php
     *
     * @Then I should see ":text" on the page
     *
     * @param string $text Text to search for
     * @return void
     * @throws ExpectationException
     */
    public function plaintext_appears_on_page(string $text): void {
        if (!str_contains($this->getSession()->getPage()->getContent(), $text)) {
            throw new ExpectationException("Text \"$text\" does not appear on the page", $this->getSession());
        }
    }

    /**
     * Reset a config value to the empty string
     * It's not possible to use the "The following config values are set as admin" step to unset a value
     *
     * @Given I unset ":value" in ":plugin"
     *
     * @param string $value
     * @param string $plugin
     * @return void
     */
    public function i_unset_a_config_value(string $value, string $plugin): void {
        unset_config($value, $plugin);
    }

}
