<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
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
 * Handles AJAX requests for the certificate module.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

$templateid = required_param('tid', PARAM_INT);
$values = required_param('values', PARAM_RAW);
$values = json_decode($values);

require_login();

$template = \tool_certificate\template::instance($templateid);

// Make sure the user has the required capabilities.
$template->require_manage();

// Loop through the data.
foreach ($values as $value) {
    $element = new stdClass();
    $element->id = $value->id;
    $element->posx = $value->posx;
    $element->posy = $value->posy;
    $DB->update_record('tool_certificate_elements', $element);
    // TODO will be moved to WS.
}
