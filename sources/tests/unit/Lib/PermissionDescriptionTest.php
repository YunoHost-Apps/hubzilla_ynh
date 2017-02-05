<?php
/*
 * Copyright (c) 2016 Hubzilla
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// Global namespace for fully qualified \App class.
namespace {
	// General channel permissions in boot.php
	// 0 = Only you
	define ( 'PERMS_PUBLIC'     , 0x0001 ); // anybody
	define ( 'PERMS_NETWORK'    , 0x0002 ); // anybody in this network
	define ( 'PERMS_SITE'       , 0x0004 ); // anybody on this site
	define ( 'PERMS_CONTACTS'   , 0x0008 ); // any of my connections
	define ( 'PERMS_SPECIFIC'   , 0x0080 ); // only specific connections
	define ( 'PERMS_AUTHED'     , 0x0100 ); // anybody authenticated (could include visitors from other networks)
	define ( 'PERMS_PENDING'    , 0x0200 ); // any connections including those who haven't yet been approved
	// log levels in boot.php
	define ( 'LOGGER_DEBUG',           2 );

	// Stub global fully qualified \App class for static function calls
	class App {
		// Stub get_hostname()
		public static function get_hostname() {
			return 'phpunit';
		}
	}
}

// Stub global functions used in PermissionDescription with the help of
// PHP's namespace resolution rules.
namespace Zotlabs\Lib {
	// Stub global translate function t()
	function t($s) {
		return $s;
	}
	// Stub global log function logger()
	function logger($msg, $level = LOGGER_NORMAL, $priority = LOG_INFO) {
		// doesn't matter
	}
}

// regular namespace for this unit test
namespace Zotlabs\Tests\Unit\Lib {

	use Zotlabs\Tests\Unit\UnitTestCase;
	use Zotlabs\Lib\PermissionDescription;

	/**
	 * @brief Unit Test case for ConnectionPool class.
	 */
	class PermissionDescriptionTest extends UnitTestCase {

		public function testFromDescription() {
			$permDesc = PermissionDescription::fromDescription('test');
			$permDesc2 = PermissionDescription::fromDescription('test');
			$permDesc3 = PermissionDescription::fromDescription('test2');

			$this->assertEquals($permDesc, $permDesc2);
			$this->assertNotEquals($permDesc, $permDesc3);
		}

		public function testFromStandalonePermission() {
			$permDescUnknown = PermissionDescription::fromStandalonePermission(-1);
			$permDescSelf = PermissionDescription::fromStandalonePermission(0);

			$this->assertNull($permDescUnknown);
			$this->assertNotNull($permDescSelf);
		}

		public function testFromGlobalPermission() {
			//$permDesc = PermissionDescription::fromGlobalPermission('view_profile');

			$this->markTestIncomplete(
				'For this test we need more stubs...'
			);
		}

		public function testGetPermissionDescription() {

			// fromStandalonePermission uses get_permission_description(), so that will not help
			//$permDescSelf = PermissionDescription::fromStandalonePermission(0);
			//$permDescPublic = PermissionDescription::fromStandalonePermission(PERMS_PUBLIC);

			$this->markTestIncomplete(
				'For this test we need a mock of PermissionDescription...'
			);
			//$permDescSelf =
			//$this->assertEquals($permDescSelf->, 'Only me');
			//$this->assertEquals($permDescPublic, 'Public');
		}
	}
}
