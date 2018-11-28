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
 * Unit tests for functions that deals with capabilities.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_capabilities_test_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test the can_manage
     */
    public function test_can_manage() {
        global $DB;

        $certificate1 = \tool_certificate\template::create((object)['name' => 'Certificate 1']);
        $certificate2 = \tool_certificate\template::create((object)['name' => 'Certificate 2', 'tenantid' => 2]);

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $this->setUser($manager);

        // Managers can manage templates default tenant.
        $this->assertEquals(true, $certificate1->can_manage());

        // Managers can't manage templates on other tenants by default.
        $this->assertEquals(false, $certificate2->can_manage());

        assign_capability('tool/certificate:manageforalltenants', CAP_ALLOW, $managerrole->id, \context_system::instance()->id);

        // Now manager can manage templates in all tenants.
        $this->assertEquals(true, $certificate1->can_manage());
        $this->assertEquals(true, $certificate2->can_manage());
    }

    /**
     * Test the can_manage
     */
    public function test_can_issue() {
        global $DB;

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager1 = $this->getDataGenerator()->create_user();
        $manager2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager1->id);
        $this->getDataGenerator()->role_assign($managerrole->id, $manager2->id);

        $tenantmanager = new \tool_tenant\manager();
        $tenantmanager->create_tenant_quick();
        $tenants = $tenantmanager->get_tenants();
        $tenant = array_pop($tenants);
        $this->getDataGenerator()->get_plugin_generator('tool_tenant')->allocate_user($manager2->id, $tenant->get('id'));

        $this->setUser($manager1);

        $certificate1 = \tool_certificate\template::create((object)['name' => 'Certificate 1']);
        $certificate2 = \tool_certificate\template::create((object)['name' => 'Certificate 2', 'tenantid' => $tenant->get('id')]);
        $certificate3 = \tool_certificate\template::create((object)['name' => 'Certificate 3', 'tenantid' => 0]);

        // Managers can issue templates by default on same tenant and on shared templates, but not for other tenants
        $this->assertEquals(true, $certificate1->can_issue());
        $this->assertEquals(false, $certificate2->can_issue());
        $this->assertEquals(true, $certificate3->can_issue());

        $this->setUser($manager2);

        $this->assertEquals(true, $certificate1->can_issue()); // TODO: should be false. take a closer look at tool_tenant.
        $this->assertEquals(false, $certificate2->can_issue()); // TODO: should be true. take a closer look at tool_tenant.
        $this->assertEquals(true, $certificate3->can_issue());

        assign_capability('tool/certificate:issueforalltenants', CAP_ALLOW, $managerrole->id, \context_system::instance()->id);

        // Now can issue in all tenants.
        $this->assertEquals(true, $certificate1->can_issue());
        $this->assertEquals(true, $certificate2->can_issue());
        $this->assertEquals(true, $certificate3->can_issue());

        $this->setUser($manager1);

        $this->assertEquals(true, $certificate1->can_issue());
        $this->assertEquals(true, $certificate2->can_issue());
        $this->assertEquals(true, $certificate3->can_issue());
    }
}