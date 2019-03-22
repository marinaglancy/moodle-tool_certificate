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
 * This file contains the form for handling editing a certificate element.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

use tool_wp\modal_form;

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/includes/colourpicker.php');

\MoodleQuickForm::registerElementType('certificate_colourpicker',
    $CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/includes/colourpicker.php',
    'moodlequickform_tool_certificate_colourpicker');

/**
 * The form for handling editing a certificate element.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_element_form extends modal_form {

    /**
     * @var \tool_certificate\element The element object.
     */
    protected $element;

    /** @var template */
    protected $template;

    /**
     * Get template
     *
     * @return template
     */
    protected function get_template() : template {
        return $this->get_element()->get_template();
    }

    /**
     * Get element
     *
     * @return element
     */
    protected function get_element() : element {
        if ($this->element === null) {
            if (!empty($this->_ajaxformdata['id'])) {
                $this->element = element::instance($this->_ajaxformdata['id']);
            } else {
                $this->element = element::instance(0, (object)['pageid' => $this->_ajaxformdata['pageid'],
                    'element' => $this->_ajaxformdata['element']]);
            }
        }
        return $this->element;
    }

    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        // Empty header that will not be displayed but at the same time advanced elements will work.
        $mform->addElement('header', 'general', '');
        $mform->setDisableShortforms(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $mform->addElement('hidden', 'element');
        $mform->setType('element', PARAM_ALPHANUMEXT);

        $this->get_element()->render_form_elements($mform);

        $this->add_action_buttons(true);
    }

    /**
     * Fill in the current page data for this certificate.
     */
    public function definition_after_data() {
        $this->element->definition_after_data($this->_form);

        if (array_key_exists('posx', $this->_ajaxformdata) && $this->_form->elementExists('posx')) {
            $this->_form->getElement('posx')->setValue($this->_ajaxformdata['posx']);
        }
        if (array_key_exists('posy', $this->_ajaxformdata) && $this->_form->elementExists('posy')) {
            $this->_form->getElement('posy')->setValue($this->_ajaxformdata['posy']);
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        $errors = array();
        $errors += $this->element->validate_form_elements($data, $files);
        return $errors;
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
     */
    public function require_access() {
        $this->get_template()->require_manage();
    }

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public function process(\stdClass $data) {
        $this->get_element()->save_form_elements($data);
        $data = $this->get_element()->to_record();
        $data->html = $this->get_element()->render_html();
        $data->name = format_string($data->name);
        return $data;
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $this->set_data($this->_ajaxformdata);
    }
}
