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
 * File contains the unit tests for the certificate class.
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the certificate class.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_cerficate_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test count_issues_for_template
     */
    public function test_count_issues_for_template() {
        global $DB;

        $this->setAdminUser();

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $certificate2 = $this->get_generator()->create_template((object)['name' => 'Certificate 2']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $certificate1->issue_certificate($user1->id);

        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));

        $certificate2->issue_certificate($user2->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));
        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_template($certificate2->get_id()));

        $this->assertEquals(3, \tool_certificate\certificate::count_issues_for_template(0));

        // Create certificate in another tenant.
        /** @var tool_tenant_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $cat3 = $this->getDataGenerator()->create_category();
        $tenant = $tenantgenerator->create_tenant(['categoryid' => $cat3->id]);

        $cert3name = 'Certificate 3';
        $certificate3 = $this->get_generator()->create_template((object)['name' => $cert3name, 'categoryid' => $cat3->id]);

        $tenantgenerator->allocate_user($user3->id, $tenant->id);
        $tenantgenerator->allocate_user($user4->id, $tenant->id);

        $certificate3->issue_certificate($user3->id);
        $certificate3->issue_certificate($user4->id);

        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_template($certificate3->get_id()));

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $tenantgenerator->allocate_user($manager->id, $tenant->id);

        $this->setUser($manager);

        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_template($certificate3->get_id()));
    }

    /**
     * Test get_issues_for_template
     */
    public function test_get_issues_for_template() {
        global $DB;

        $this->setAdminUser();

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $certificate2 = $this->get_generator()->create_template((object)['name' => 'Certificate 2']);

        $user1 = $this->getDataGenerator()->create_user();

        $certificate1->issue_certificate($user1->id);
        $certificate2->issue_certificate($user1->id);

        $issues = \tool_certificate\certificate::get_issues_for_template($certificate1->get_id(), 0, 100);
        $this->assertEquals(1, count($issues));

        $issue = array_pop($issues);
        $this->assertEquals('Certificate 1', $issue->name);

        // Now test with manager with no permission on all tenants.
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $this->setUser($manager);

        $issues = \tool_certificate\certificate::get_issues_for_template($certificate1->get_id(), 0, 100);
        $this->assertEquals(1, count($issues));

        $issue = array_pop($issues);
        $this->assertEquals('Certificate 1', $issue->name);
        $this->assertEquals($certificate1->get_id(), $issue->templateid);

        $issues = \tool_certificate\certificate::get_issues_for_template($certificate2->get_id(), 0, 100);
        $this->assertEquals(1, count($issues));

        $issue = array_pop($issues);
        $this->assertEquals('Certificate 2', $issue->name);
        $this->assertEquals($certificate2->get_id(), $issue->templateid);
    }

    /**
     * Test count issues for user.
     */
    public function test_count_issues_for_user() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $certificate2 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $certificate3 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_user($user1->id));

        $certificate2->issue_certificate($user1->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));

        $certificate1->issue_certificate($user2->id);
        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_user($user2->id));

        $certificate2->issue_certificate($user2->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user2->id));

        $certificate3->issue_certificate($user2->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));
        $this->assertEquals(3, \tool_certificate\certificate::count_issues_for_user($user2->id));

        $this->assertEquals(5, \tool_certificate\certificate::count_issues_for_user(0));
    }

    /**
     * Test get issues for user.
     */
    public function test_get_issues_for_user() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $this->assertEquals(0, count(\tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100)));

        $certificate1->issue_certificate($user1->id);
        $issues = \tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100);
        $this->assertEquals(1, count($issues));
        $firstissue = reset($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $firstissue->userid);

        $certificate1->issue_certificate($user1->id);

        $issues = \tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100);
        $this->assertEquals(2, count($issues));
        $firstissue = reset($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $firstissue->userid);

        $secondissue = next($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $secondissue->userid);
        $this->assertFalse($firstissue->id == $secondissue->id);
    }

    /**
     * Test count issues for course
     */
    public function test_count_issues_for_course() {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_and_enrol($course1, 'student');
        $user2 = $this->getDataGenerator()->create_and_enrol($course1, 'student');
        $user3 = $this->getDataGenerator()->create_and_enrol($course1, 'student');
        $user4 = $this->getDataGenerator()->create_and_enrol($course1, 'student');
        $user5 = $this->getDataGenerator()->create_and_enrol($course1, 'student');

        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $user2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $user3->id]);

        $template1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        // Create a dummy assignment to test groupmode.
        $module = $this->getDataGenerator()->create_module('assignment', ['course' => $course1->id]);
        $cm = get_coursemodule_from_instance('assignment', $module->id);
        // Using dummy component name.
        $component = 'mod_myawesomecert';

        $template1->issue_certificate($user1->id, null, [], $component, $course1->id);
        $template1->issue_certificate($user2->id, null, [], $component, $course1->id);
        $template1->issue_certificate($user3->id, null, [], $component, $course1->id);
        $template1->issue_certificate($user4->id, null, [], $component, $course1->id);

        $this->assertEmpty(\tool_certificate\certificate::count_issues_for_course($template1->get_id(), $course2->id, $component,
            null, null));
        $this->assertEquals(4, \tool_certificate\certificate::count_issues_for_course($template1->get_id(), $course1->id,
            $component, NOGROUPS, null));
        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_course($template1->get_id(), $course1->id,
            $component, VISIBLEGROUPS, $group1->id));
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_course($template1->get_id(), $course1->id,
            $component, VISIBLEGROUPS, $group2->id));

        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $user1->id]);
        $this->assertEquals(3, \tool_certificate\certificate::count_issues_for_course($template1->get_id(), $course1->id,
            $component, true, $group2->id));
    }

    /**
     * Test get issues for course
     */
    public function test_get_issues_for_course() {
        $course1 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_and_enrol($course1, 'student');
        $user2 = $this->getDataGenerator()->create_and_enrol($course1, 'student');

        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $user2->id]);

        $template1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        // Create a dummy assignment to test groupmode.
        $module = $this->getDataGenerator()->create_module('assignment', ['course' => $course1->id]);
        $cm = get_coursemodule_from_instance('assignment', $module->id);
        // Using dummy component name.
        $component = 'mod_myawesomecert';

        $template1->issue_certificate($user1->id, null, [], $component, $course1->id);
        $template1->issue_certificate($user2->id, null, [], $component, $course1->id);

        $issues = \tool_certificate\certificate::get_issues_for_course($template1->get_id(), $course1->id, $component,
            NOGROUPS, null, 0, 100, 'userid ASC');
        $this->assertCount(2, $issues);
        $issue1 = reset($issues);
        $this->assertEquals($user1->id, $issue1->userid);
        $this->assertEquals($course1->id, $issue1->courseid);
        $this->assertEquals($template1->get_id(), $issue1->templateid);
        $issue2 = next($issues);
        $this->assertEquals($user2->id, $issue2->userid);
        $this->assertEquals($course1->id, $issue1->courseid);
        $this->assertEquals($template1->get_id(), $issue1->templateid);

        $issues = \tool_certificate\certificate::get_issues_for_course($template1->get_id(), $course1->id, $component,
            VISIBLEGROUPS, $group1->id, 0, 100, '');
        $this->assertCount(1, $issues);
        $issue1 = reset($issues);
        $this->assertEquals($user2->id, $issue1->userid);
        $this->assertEquals($course1->id, $issue1->courseid);
        $this->assertEquals($template1->get_id(), $issue1->templateid);
    }

    /**
     * Test verify
     */
    public function test_verify() {
        global $DB;

        $this->setAdminUser();
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $issueid1 = $certificate1->issue_certificate($user1->id);

        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        // First, an invalid code must not trigger event.
        $sink = $this->redirectEvents();

        $result = \tool_certificate\certificate::verify('invalidCode1');

        $events = $sink->get_events();
        $this->assertCount(0, $events);

        $this->assertFalse($result->success);
        $this->assertTrue(empty($result->issues));

        // A valid code will trigger the event.
        $sink = $this->redirectEvents();

        $result = \tool_certificate\certificate::verify($code1);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_verified', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::verification_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertTrue($result->success);
        $this->assertEquals($result->issue->id, $issueid1);

        // Now test with manager with no permission on all tenants.
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $this->setUser($manager);

        $result = \tool_certificate\certificate::verify($code1);

        $this->assertTrue($result->success);
        $this->assertEquals($result->issue->id, $issueid1);
    }

    /**
     * Test generate code.
     */
    public function test_generate_code() {
        $code1 = \tool_certificate\certificate::generate_code();
        $code2 = \tool_certificate\certificate::generate_code();
        $this->assertFalse($code1 == $code2);
    }

    /**
     * Test count_templates_in_category.
     */
    public function test_count_templates_in_category() {
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Cat1']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Cat2', 'parent' => $category1->id]);
        $category3 = $this->getDataGenerator()->create_category(['name' => 'Cat3', 'parent' => $category1->id]);
        $category4 = $this->getDataGenerator()->create_category(['name' => 'Cat4', 'parent' => $category2->id]);

        $template1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1',
            'contextid' => $category1->get_context()->id]);
        $template2 = $this->get_generator()->create_template((object)['name' => 'Certificate 2',
            'contextid' => $category2->get_context()->id]);
        $template3 = $this->get_generator()->create_template((object)['name' => 'Certificate 3',
            'contextid' => $category4->get_context()->id]);
        $template4 = $this->get_generator()->create_template((object)['name' => 'Certificate 4',
            'contextid' => $category4->get_context()->id]);

        /*
         * Now we have
         * $category1
         *      $template1
         *      $category2
         *          $template2
         *          $category4
         *              $template3
         *              $template4
         *      $category3
         * structure.
         */

        $this->assertEquals(4, \tool_certificate\certificate::count_templates_in_category($category1));
        $this->assertEquals(3, \tool_certificate\certificate::count_templates_in_category($category2));
        $this->assertEmpty(\tool_certificate\certificate::count_templates_in_category($category3));
        $this->assertEquals(2, \tool_certificate\certificate::count_templates_in_category($category4));
    }
}
