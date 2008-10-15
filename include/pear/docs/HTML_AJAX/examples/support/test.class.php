<?php
/**
 * Test class used in other examples
 * Constructors and private methods marked with _ are never exported in proxies to JavaScript
 * 
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
class test {
	function test() {
	}
	function _private() {
	}
	function echo_string($string) {
		return "From PHP: ".$string;
	}
	function echo_strings($strings) {
		return $strings;
	}
	function slow_echo_string($string) {
		sleep(2);
		return "From PHP: ".$string;
	}
	function error_test($string) {
		trigger_error($string);
	}
	function multiarg() {
		$args = func_get_args();
		return "passed in ".count($args)." args ".var_export($args, TRUE);
	}
	function cookies() {
		return $_COOKIE;
	}
	function echo_data($data) {
		return array('From PHP:'=>$data);
	}
	function dump($data) {
		return print_r($data, true);
	}
	function unicode_data() {
		$returnData = array('word' => mb_convert_encoding('Français','UTF-8'), 'suggestion' => array(mb_convert_encoding('Français','UTF-8'), mb_convert_encoding('caractères','UTF-8')));
		return $returnData;
	}

	function test1($in) {
		return $in;
	}
	function test2($in) {
		return $in;
	}
	function test3($in) {
		return $in;
	}
}

if (isset($_GET['TEST_CLASS'])) {
	$t = new test();
	var_dump($t->echo_string('test string'));
	var_dump($t->slow_echo_string('test string'));
	var_dump($t->error_test('test string'));
	var_dump($t->multiarg('arg1','arg2','arg3'));
}
?>
