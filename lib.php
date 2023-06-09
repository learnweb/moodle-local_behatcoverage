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
 * Lib functions for local_behatcoverage.
 *
 * @package    local_behatcoverage
 * @copyright  2023 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP;

/**
 * Function that starts
 */
function local_behatcoverage_after_config() {
    // if (defined('BEHAT_SITE_RUNNING')) {
        if ($plugintocheck = getenv('BEHAT_COVERAGE_FOR')) {
            local_behatcoverage_start_coverage($plugintocheck);
        }
    // }
}

function local_behatcoverage_start_coverage($plugintocheck) {
    global $CFG;

    require $CFG->dirroot . '/vendor/autoload.php';

    $plugininfo = core_plugin_manager::instance()->get_plugin_info($plugintocheck);

    $filter = new \SebastianBergmann\CodeCoverage\Filter();
    $filter->includeDirectory($plugininfo->rootdir);
    $filter->excludeDirectory($plugininfo->rootdir . '/lang/');
    $filter->excludeDirectory($plugininfo->rootdir . '/tests/', '_test.php');
    $filter->excludeDirectory($plugininfo->rootdir . '/thirdparty/');

    $driver = (new \SebastianBergmann\CodeCoverage\Driver\Selector)->forLineCoverage($filter);
    $codecoverage = new CodeCoverage($driver, $filter);
    $codecoverage->includeUncoveredFiles();
    if (file_exists($CFG->dataroot . '/behat.cov')) {
        $past = include $CFG->dataroot . '/behat.cov';
        $codecoverage->merge($past);
    }
    core_shutdown_manager::register_function('local_behatcoverage_shutdown', [$codecoverage]);
    $codecoverage->start('behat');
}

function local_behatcoverage_shutdown(CodeCoverage $codecoverage) {
    global $CFG;

    $codecoverage->stop();
    $php = new PHP();
    $php->process($codecoverage, $CFG->dataroot . '/behat.cov');
}