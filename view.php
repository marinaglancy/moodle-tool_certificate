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
 * View issued certificate as pdf.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$issuecode = required_param('code', PARAM_TEXT);
$preview = optional_param('preview', false, PARAM_BOOL);
if ($preview) {

    $templateid = required_param('templateid', PARAM_INT);
    require_login();
    $template = \tool_certificate\template::find_by_id($templateid);
    if ($template->can_manage()) {
        $template->generate_pdf(true);
    }
} else {

    $issue = $DB->get_record('tool_certificate_issues', ['code' => $issuecode]);
    $context = context_system::instance();
    if (has_capability('tool/certificate:viewallcertificates', $context) ||
            has_capability('tool/certificate:verifyallcertificates', $context) ||
            (isloggedin() && $issue->userid == $USER->id)) {

        $template = \tool_certificate\template::find_by_id($issue->templateid);
        $template->generate_pdf(false, $issue);
    }
}

print_error('notfound');
