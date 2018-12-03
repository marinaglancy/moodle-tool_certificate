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
class tool_certificate_template_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Get tenant generator
     * @return tool_tenant_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test certificate template creation.
     */
    public function test_create() {
        global $DB;

        // There are no certificate templates in the beginning.
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        // Create new certificate.
        $cert1name = 'Certificate 1';
        $certificate1 = \tool_certificate\template::create((object)['name' => $cert1name]);
        $this->assertEquals(1, $DB->count_records('tool_certificate_templates'));

        $this->assertEquals(\tool_tenant\tenancy::get_default_tenant_id(), $certificate1->get_tenant_id());
        $this->assertEquals($cert1name, $certificate1->get_name());
        $this->assertEquals(\context_system::instance(), $certificate1->get_context());

        $events = $sink->get_events();
        $this->assertCount(2, $events); // There will be a tenant_created event.
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_created', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals($certificate1->edit_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        // Create new certificate.
        $cert2name = 'Certificate 2';
        $certificate2 = \tool_certificate\template::create((object)['name' => $cert2name]);
        $this->assertEquals(2, $DB->count_records('tool_certificate_templates'));

        $this->assertEquals($cert2name, $certificate2->get_name());
        $this->assertEquals($cert2name, $DB->get_field('tool_certificate_templates', 'name', ['id' => $certificate2->get_id()]));

        // Create certificate in another tenant.
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $tenant = $tenantgenerator->create_tenant();

        $certificate3 = \tool_certificate\template::create((object)['name' => $cert2name, 'tenantid' => $tenant->id]);
        $this->assertEquals(3, $DB->count_records('tool_certificate_templates'));
        $this->assertEquals($tenant->id, $DB->get_field('tool_certificate_templates', 'tenantid', ['id' => $certificate3->get_id()]));
    }

    /**
     * Test change a template name.
     */
    public function test_save() {
        // Create new certificate.
        $certname1 = 'Certificate 1';
        $certname2 = 'Certificate Updated';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname1]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->save((object)['name' => $certname2]);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_updated', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals($certificate1->edit_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals($certname2, \tool_certificate\template::find_by_name($certname2)->get_name());
        $this->assertFalse(\tool_certificate\template::find_by_name($certname1));
    }

    /**
     * Find a certificate template given it's name.
     */
    public function test_find_by_name() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::find_by_name($certname)->get_name());
    }

    /**
     * Find a certificate template given it's id.
     */
    public function test_find_by_id() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::find_by_id($certificate1->get_id())->get_name());
    }

    /**
     * Test duplicate a template.
     */
    public function test_duplicate() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate2 = $certificate1->duplicate();
        $expectedname = $certname . ' (' . strtolower(get_string('duplicate', 'tool_certificate')) . ')';
        $this->assertEquals($expectedname, $certificate2->get_name());
        $this->assertFalse($certificate1->get_id() == $certificate2->get_id());
    }

    /**
     * Test delete an empty template.
     */
    public function test_delete_empty_template() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->delete();

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_deleted', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals($certificate1->edit_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));
    }

    /**
     * Test add page to template.
     */
    public function test_add_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate1->add_page();
        $this->assertEquals(1, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
    }

    /**
     * Test save page.
     */
    public function test_save_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $pageid = $certificate1->add_page();
        $pagedata = (object)['tid' => $certificate1->get_id(),
                             'pagewidth_'.$pageid => 333, 'pageheight_'.$pageid => 444,
                             'pageleftmargin_'.$pageid => 333, 'pagerightmargin_'.$pageid => 444];
        $certificate1->save_page($pagedata);
        $this->assertTrue($DB->record_exists('tool_certificate_pages', ['templateid' => $certificate1->get_id(),
            'width' => 333, 'height' => 444]));
    }

    /**
     * Test issue certificate to user.
     */
    public function test_issue_certificate() {
        global $DB;

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $issueid1 = $certificate1->issue_certificate($user1->id);

        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_issued', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::view_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        $certificate1->issue_certificate($user2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user2->id]));

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(2, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user1->id]));

        $certificate1->issue_certificate($user2->id);
        $certificate1->issue_certificate($user2->id);

        $this->assertEquals(3, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user2->id]));
    }

    /**
     * Test get issues for user.
     */
    public function test_revoke_issue() {
        global $DB;

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $issueid1 = $certificate1->issue_certificate($user1->id);
        $issueid2 = $certificate1->issue_certificate($user2->id);
        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->revoke_issue($issueid1);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_revoked', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $moodlepage = new \moodle_url('/admin/tool/certificate/view.php', ['code' => $code1]);
        $this->assertEquals(\tool_certificate\template::view_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        $certificate1->revoke_issue($issueid2);

        $this->assertEquals(0, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));
    }
}
