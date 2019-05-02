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
 * Customcert module core interaction API
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Serves certificate issues and other files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool|null false if file not found, does not return anything if found - just send the file
 */
function tool_certificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');

    // We are positioning the elements.
    if ($filearea === 'image') {
        if (!\tool_certificate\template::can_verify_loose()) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = '/' . $context->id . '/tool_certificate/image/' . $relativepath;

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload);
    }

    // Elements can use several fileareas defined in tool_certificate.
    if ($filearea === 'element' || $filearea === 'elementaux') {
        $elementid = array_shift($args);
        $template = \tool_certificate\template::find_by_element_id($elementid);
        $template->require_manage();

        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/' . implode('/', $args) . '/';
        }
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'tool_certificate', $filearea, $elementid, $filepath, $filename);
        if (!$file) {
            return;
        }
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function tool_certificate_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $url = new moodle_url('/admin/tool/certificate/my_certificates.php', array('userid' => $user->id));
    $node = new core_user\output\myprofile\node('miscellaneous', 'toolcertificatemy',
        get_string('mycertificates', 'tool_certificate'), null, $url);
    $tree->add_node($node);
}

/**
 * Handles editing the 'name' of the element in a list.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param string $newvalue
 * @return \core\output\inplace_editable
 */
function tool_certificate_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    if ($itemtype === 'elementname') {
        // Validate access.
        external_api::validate_context(context_system::instance());
        $element = \tool_certificate\element::instance($itemid);
        $element->get_template()->require_manage();

        $element->save((object)['name' => $newvalue]);
        return $element->get_inplace_editable();
    }

    if ($itemtype === 'templatename') {
        $template = \tool_certificate\template::instance($itemid);
        $template->require_manage();
        external_api::validate_context(context_system::instance());
        $template->require_manage();
        $template->save((object)['name' => $newvalue]);
        return $template->get_editable_name();
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function tool_certificate_get_fontawesome_icon_map() {
    return [
        'tool_certificate:download' => 'fa-download'
    ];
}

/**
 * Callback to filter form-potential-users-selector
 * @param string $area
 * @param int $itemid
 * @return array
 */
function tool_certificate_potential_users_selector($area, $itemid) {
    if ($area !== 'issue') {
        return null;
    }

    $template = \tool_certificate\template::instance($itemid);

    if ($template->get_tenant_id() == 0 && \tool_certificate\template::can_issue_or_manage_all_tenants()) {
        $join = '';
        $params = [];
        $where = ' (ci.id IS NULL OR (ci.expires > 0 AND ci.expires < :now))';
    } else if ($template->can_issue()) {
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('u', $template->get_tenant_id());
        $where .= ' AND (ci.id IS NULL OR (ci.expires > 0 AND ci.expires < :now))';
    } else {
        throw new required_capability_exception(context_system::instance(), 'tool/certificate:issue', 'nopermissions', 'error');
    }

    $join .= ' LEFT JOIN {tool_certificate_issues} ci ON u.id = ci.userid AND ci.templateid = :templateid';

    $params['templateid'] = $itemid;
    $params['now'] = time();

    return [$join, $where, $params];
}

/**
 * Implementation of callback 'wp_registration_stats' called from 'tool_wp_registration_stats'
 *
 * @param bool $usestrings return data in human readable form to be displayed on the "Registration" page
 * @return array
 */
function tool_certificate_wp_registration_stats($usestrings = false) {
    global $DB;
    $count = $DB->count_records('tool_certificate_templates', []);
    $issues = $DB->count_records('tool_certificate_issues', []);
    return [
        'wpcertificates' => $count,
        'wpcertificatesissues' => $issues,
    ];
}
