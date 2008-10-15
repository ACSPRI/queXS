<?php
/**
 * AJAX form submission example
 *
 * @category   HTML
 * @package    AJAX
 * @author     Arpad Ray <arpad@php.net>
 * @author     Laurent Yaish <laurenty@gmail.com>
 * @copyright  2005 Arpad Ray
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */


?>
<html>
    <head>
        <script type="text/javascript" src="server.php?client=all&stub=test"></script>
    </head>
    <body>
        <pre id="target">
        </pre>
	<form action="server.php" method="post" onsubmit="return !HTML_AJAX.formSubmit(this, 'target', {className: 'test', methodName:'multiarg'});">
		<table>
			<tr>
				<td>Text</td>
				<td><input type="text" name="test_text" value="example" /></td>
			</tr>
			<tr>
				<td>Single Select</td>
				<td>
					<select name="test_select">
				                <option value="example1">Example 1</option>
				                <option value="example2">Example 2</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Multi Select</td>
				<td>
					<select name="test_select_multiple[]" multiple="multiple">
						<option value="examplea">Example A</option>
						<option value="exampleb">Example B</option>
						<option value="examplec">Example C</option>
						<option value="exampled">Example D</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Single Checkbox</td>
				<td><input type="checkbox" name="single_checkbox" value="single_check1" /></td>
			</tr>
			<tr>
				<td>Multi Checkboxes</td>
				<td>
					<input type="checkbox" name="multi_checkbox[]" value="multi_check1" />1
					<input type="checkbox" name="multi_checkbox[]" value="multi_check2" />2
					<input type="checkbox" name="multi_checkbox[]" value="multi_check3" />3
				</td>
			</tr>
			<tr>
				<td>Radio Buttons</td>
				<td>
					<input type="radio" name="test_radio" value="radio_1" />1
					<input type="radio" name="test_radio" value="radio_2" />2
					<input type="radio" name="test_radio" value="radio_3" />3
				</td>
			</tr>
			<tr>
				<td>Textarea</td>
				<td>
					<textarea name="long_text">type a long string in here....</textarea>
				</td>
			</tr>
		</table>
	    <input type="submit" name="submit" value="Submit form" />
        </form>

	<h3>JavaScript callback function target test</h3>
	<form action="server.php" method="post" onsubmit="return !HTML_AJAX.formSubmit(this, function(result) { document.getElementById('target').innerHTML = result; }, {className: 'test', methodName:'multiarg'});">

		<table>
			<tr>
				<td>Text</td>
				<td><input type="text" name="test_text" value="example" /></td>
			</tr>
		</table>
	</form>
    </body>
</html>
