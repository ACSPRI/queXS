<?php
/**
 * Require the action class
 */
require_once 'HTML/AJAX/Action.php';

class testHaa {
	function updateClassName() {
		$response = new HTML_AJAX_Action();

		$response->assignAttr('test','className','test');

		return $response;
	}

	function greenText($id) {
		$response = new HTML_AJAX_Action();
		$response->assignAttr($id,'style','color: green');
		return $response;
	}

	function highlight($id) {
		$response = new HTML_AJAX_Action();
		$response->assignAttr($id,'style','background-color: yellow');
		return $response;
	}

	function duplicate($id,$dest) {
		// there really should be an action method to do this
		$response = new HTML_AJAX_Action();
		$response->insertScript("
			var newNode = document.getElementById('$id').cloneNode(true);
			newNode.id = 'newNode';
			document.getElementById('$dest').appendChild(newNode);");
		return $response;
	}
}
