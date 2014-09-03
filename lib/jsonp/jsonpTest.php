<?php

require_once(__DIR__ . '/jsonp.php');

/**
 * Class JSONPTest
 * @group Voce
 * @group JSONP
 */
class JSONPTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$this->good_callback     = 'jQuery19103165583838708699_1367588311424';
		$this->bad_callback      = 'alert%28document.cookie%29%3Bfoo';
		$this->reserved_callback = 'switch';
	}

	public function testIsUTF8() {
		$this->assertTrue( \Voce\JSONP::is_utf8($this->good_callback));
	}


	public function testHasReservedWord() {
		$this->assertTrue( \Voce\JSONP::has_reserved_word($this->reserved_callback));
	}

	public function testHasReservedWordFalse() {
		$this->assertFalse( \Voce\JSONP::has_reserved_word($this->good_callback));
	}

	public function testHasValidSyntax() {
		$this->assertTrue( \Voce\JSONP::has_valid_syntax($this->good_callback));
	}

	public function testHasValidSyntaxDotNotation() {
		$this->assertTrue( \Voce\JSONP::has_valid_syntax('Foo.bar'));
	}

	public function testHasValidSyntaxjQuery(){
		$this->assertTrue( \Voce\JSONP::has_valid_syntax('$.ajaxHandler'));
	}

	public function testHasValidSyntaxSquareBrackets(){
		$this->assertTrue( \Voce\JSONP::has_valid_syntax('someClass["callback"]'));
	}

	public function testHasValidSyntaxSquareBracketsWithoutParens(){
		$this->assertTrue( \Voce\JSONP::has_valid_syntax('someClass[callback]'));
	}

	public function testHasValidSyntaxDotNotationSquareBrackets(){
		$this->assertTrue( \Voce\JSONP::has_valid_syntax('someClass.callbackList["callback"]'));
	}

	public function testHasValidSyntaxFalse() {
		$this->assertFalse( \Voce\JSONP::has_valid_syntax('foo()'));
	}

	public function testIsValidCallback(){
		$this->assertTrue( \Voce\JSONP::is_valid_callback($this->good_callback));
	}

	public function testIsValidCallbackFalse(){
		$this->assertFalse( \Voce\JSONP::is_valid_callback($this->bad_callback));
	}

	public function testIsValidCallbackKeywordFalse(){
		$this->assertFalse( \Voce\JSONP::is_valid_callback('abstract'));
	}

	public function testIsValidCallbackSyntaxFalse(){
		$this->assertFalse( \Voce\JSONP::is_valid_callback('foo()'));
	}
}