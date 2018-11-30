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
 * File contains the unit tests for the webservices.
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the webservices.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @category   test
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_external_test_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue() {
        global $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = \tool_certificate\template::create((object)['name' => 'Site template']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        $result = \tool_certificate\external::delete_issue($i2);

        // We need to execute the return values cleaning process to simulate the web service server.
        external_api::clean_returnvalue(\tool_certificate\external::delete_issue_returns(), $result);

        $issues = $DB->get_records('tool_certificate_issues');
        $this->assertCount(1, $issues);

        $issue = reset($issues);
        $this->assertEquals($student1->id, $issue->userid);
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue_no_login() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = \tool_certificate\template::create((object)['name' => 'Site template']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        // Try and delete without logging in.
        $this->expectException('require_login_exception');
        \tool_certificate\external::delete_issue($i2);
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue_no_capability() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = \tool_certificate\template::create((object)['name' => 'Site template']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $this->setUser($student1);

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        // Try and delete without the required capability.
        $this->expectException('required_capability_exception');
        \tool_certificate\external::delete_issue($i2);
    }
}
