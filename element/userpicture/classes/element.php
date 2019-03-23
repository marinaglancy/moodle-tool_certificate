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
 * This file contains the certificate element userpicture's core interaction API.
 *
 * @package    certificateelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_userpicture;

use tool_certificate\element_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * The certificate element userpicture's core interaction API.
 *
 * @package    certificateelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /** @var bool $istext This is a text element, it has font, color and width limiter */
    protected $istext = false;

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        element_helper::render_form_element_width($mform, 'certificateelement_userpicture');
        element_helper::render_form_element_height($mform, 'certificateelement_userpicture');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save(\stdClass $data) {
        if (property_exists($data, 'height')) {
            $data->data = json_encode(['width' => (int) $data->width, 'height' => (int) $data->height]);
        }
        parent::save($data);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public function render($pdf, $preview, $user, $issue) {
        global $CFG;

        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return;
        }

        $imageinfo = json_decode($this->get_data());

        $context = \context_user::instance($user->id);

        // Get files in the user icon area.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon', 0);

        // Get the file we want to display.
        $file = null;
        foreach ($files as $filefound) {
            if (!$filefound->is_directory()) {
                $file = $filefound;
                break;
            }
        }

        // Show image if we found one.
        if ($file) {
            element_helper::render_image($pdf, $this, $file, [], $imageinfo->width, $imageinfo->height);
        } else if ($preview) { // Can't find an image, but we are in preview mode then display default pic.
            $location = $CFG->dirroot . '/pix/u/f1.png';
            element_helper::render_image($pdf, $this, $location,
                ['width' => 100, 'height' => 100], $imageinfo->width, $imageinfo->height);
        }
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        global $PAGE, $USER;

        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return '';
        }

        $imageinfo = json_decode($this->get_data());

        // Get the image.
        $userpicture = new \user_picture($USER);
        $userpicture->size = 1;
        $url = $userpicture->get_url($PAGE)->out(false);

        return element_helper::render_image_html($url, ['width' => 100, 'height' => 100],
            (float)$imageinfo->width, (float)$imageinfo->height);
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        // Set the image, width and height for this element.
        if (!empty($this->get_data())) {
            $imageinfo = json_decode($this->get_data());

            $element = $mform->getElement('width');
            $element->setValue($imageinfo->width);

            $element = $mform->getElement('height');
            $element->setValue($imageinfo->height);
        }

        parent::definition_after_data($mform);
    }
}
