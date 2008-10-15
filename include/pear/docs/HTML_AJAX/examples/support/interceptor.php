<?php
class Interceptor {

	/**
	 * A global interceptor runs all calls not matched by a more specific rule
	 * This example creates a not logged in error
	 */
	function intercept($className,$methodName,$params) {

		// if you were using php5 you could throw an exception instead of using trigger_error
		trigger_error("Not logged in: $className::$methodName");

		return $params;
	}

	/**
	 * A class level interceptor for the test class
	 * This examples sets the first parameter to the method name
	 */
	function test($methodName,$params) {
		$params[0] = 'Intercepted: '.$methodName.' - Original: '.$params[0];

		return $params;
	}

	/**
	 * A method level interceptor for the test::test1 method
	 * This examples sets the first parameter to boink
	 */
	function test_test1($params) {
		$params[0] = 'Intercepted: boink - Original: '.$params[0];

		return $params;
	}
}
