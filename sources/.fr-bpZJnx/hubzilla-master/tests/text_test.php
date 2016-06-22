<?php
/**
 * this file contains tests for text.php
 * 
 * @package test.util
 */

/** required, it is the file under test */
require_once('include/text.php');

/**
 * TestCase for the texter
 * 
 * @author ken restivo
 * @package test.util
 */
class TextTest extends PHPUnit_Framework_TestCase {
	public function testGoodEmail() {
		$this->assertTrue(valid_email_regex('ken@spaz.org'));
	}
	public function testGoodEmail2() {
		$this->assertTrue(valid_email_regex('ken@restivo.org'));
	}
	public function testGoodEmail3() {
		$this->assertTrue(valid_email_regex('nobody@hubzilla.com'));
	}
	public function testBadEmail() {
		$this->assertFalse(valid_email_regex('nobody!uses!these!any.more'));
	}

}