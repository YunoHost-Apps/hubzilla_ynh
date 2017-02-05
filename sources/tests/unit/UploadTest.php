<?php
/**
 * this file contains tests for the uploader
 *
 * @package test.util
 */

use PHPUnit\Framework\TestCase;

/** required, it is the file under test */
require_once('include/attach.php');

/**
 * TestCase for the uploader
 *
 * @author ken restivo
 * @package test.util
 */
class UploadTest extends TestCase {
	public function testFileNameMutipleDots() {
		$multidots = "foo.bar.baz.0.1.3.ogg";
		$this->assertEquals("audio/ogg", z_mime_content_type($multidots));
		$this->assertNotEquals("application/octet-stream", z_mime_content_type($multidots));
	}

	public function testFileNameOneDot() {
		$multidots = "foo.ogg";
		$this->assertEquals("audio/ogg", z_mime_content_type($multidots));
		$this->assertNotEquals("application/octet-stream", z_mime_content_type($multidots));
	}
}