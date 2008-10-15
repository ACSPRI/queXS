<?php
/**
 * Guestbook uses HTML_AJAX_Action class to interact with the page - the
 * javascript is all written from here
 *
 * @category   HTML
 * @package    AJAX
 * @author     Elizabeth Smith <auroraeosrose@gmail.com>
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

/**
 * Require the action class
 */
require_once 'HTML/AJAX/Action.php';

class guestbook {

	// constructor won't be exported
	function guestbook() {
		if (!isset($_SESSION['entries'])) {
			$_SESSION['entries'] = array();
		}
	}

	// data is an array of objects
	function newEntry($data) {
		//validation code is identical
		$response = new HTML_AJAX_Action();
		//remove any error nodes present
		$response->removeNode('nameError');
		$response->removeNode('emailError');
		$response->removeNode('emailError2');
		$response->removeNode('commentError');
		//checking data
		if(!isset($data['name']) or empty($data['name']))
		{
			//create error div after bad name node
			$response->createNode('name', 'div', array('class' => 'error', 'innerHTML' => 'Name is a required field', 'id' => 'nameError'), 'insertAfter');
			$error = TRUE;
		}
		if(!isset($data['email']) or empty($data['email']))
		{
			//create error div after bad name node
			$response->createNode('email', 'div', array('class' => 'error', 'innerHTML' => 'Email is a required field', 'id' => 'emailError'), 'insertAfter');
			$error = TRUE;
		}
		if($this->_checkEmail($data['email']) != TRUE)
		{
			//create error div after bad name node
			$response->createNode('email', 'div', array('class' => 'error', 'innerHTML' => 'That email address is incorrect', 'id' => 'emailError2'), 'insertAfter');
			$error = TRUE;
		}
		if(!isset($data['comments']) or empty($data['comments']))
		{
			//create error div after bad name node
			$response->createNode('comments', 'div', array('class' => 'error', 'innerHTML' => 'Comment is a required field', 'id' => 'commentError'), 'insertAfter');
			$error = TRUE;
		}
		if(!isset($error))
		{
			//clean name - strip tags and html_entity it :)
			$data['name'] = htmlentities(strip_tags($data['name']));
			//clean email - strip tags it
			$data['email'] = strip_tags($data['email']);
			//clean website - strip http://if needed
			$data['website'] = strip_tags($data['website']);
			if(strpos($data['website'], 'http://') === 0)
			{
				$data['website'] = str_replace('http://', '', $data['website']);
			}
			//clean like name
			$data['comments'] = htmlentities(strip_tags($data['comments']));
			//branch here depending on if form is new
			if($data['submit'] == 'Edit Entry')
			{
				$old = $_SESSION['entries'][$data['key']];
				//merge new data over old
				foreach($data as $key => $value)
				{
					$old[$key] = $value;
				}
				$_SESSION['entries'][$data['key']] = $old;
				//replace div innerHTML, fun fun
				$response->assignAttr('entry'.$data['key'], 'innerHTML', $this->_makeDiv($data['key'], $_SESSION['entries'][$data['key']], TRUE));
				//remove hidden input
				$response->removeNode('key');
			}
			else
			{
				$data['date'] = date('j/n/Y, h:i');
				$_SESSION['entries'][] = $data;
				end($_SESSION['entries']);
				$key = key($_SESSION['entries']);
				$response->prependAttr('guestbookList', 'innerHTML', $this->_makeDiv($key, $data));
			}
			//reset the form
			$response->assignAttr('name', 'value', '');
			$response->assignAttr('email', 'value', '');
			$response->assignAttr('website', 'value', '');
			$response->assignAttr('comments', 'value', '');
			$response->assignAttr('submit', 'value', 'Add Comments');
		}
		return $response;
	}

	// empty the guestbook
	function clearGuestbook($data) {
		$_SESSION['entries'] = array();
		$response = new HTML_AJAX_Action();
		$response->insertAlert('You will clear all entries, this cannot be undone!');
		$response->assignAttr('guestbookList', 'innerHTML', '');
		return $response;
	}

	// delete an entry from the guestbook
	function deleteEntry($id) {
		unset($_SESSION[$id]);
		$response = new HTML_AJAX_Action();
		$response->removeNode('entry'.$id);
		return $response;
	}

	// puts a guestbook entry back in
	function editEntry($id) {
		$data = $_SESSION['entries'][$id];
		$response = new HTML_AJAX_Action();
		//send to the form
		$response->assignAttr('name', 'value', $data['name']);
		$response->assignAttr('email', 'value', $data['email']);
		$response->assignAttr('website', 'value', $data['website']);
		$response->assignAttr('comments', 'value', $data['comments']);
		$response->assignAttr('submit', 'value', 'Edit Entry');
		$response->createNode('submit', 'input', array('id' => 'key', 'name' => 'key', 'type' => 'hidden', 'value' => $id), 'insertBefore');
		return $response;
	}

	function updateSelect($id)
	{
		$response = new HTML_AJAX_Action();
		$attr = array('id' => $id, 'name' => $id);
		$response->replaceNode($id, 'select', $attr);
		for ($i=1;$i<=10;$i++)
		{
			$attr = array('value' => $i, 'innerHTML' => 'Option ' . $i);
			$response->createNode($id, 'option', $attr, 'append');
		}
		return $response;
	}

	function _makeDiv($key, $data, $replace = FALSE) {
		$div = '';
		if($replace == FALSE)
		{
			$div .= '<div class="entry" id="entry'.$key.'">';
		}
		$div .= '<h3><a href="mailto:'.$data['email'].'">'.$data['name'].'</a></h3>';
		if(!empty($data['website']))
		{
			$div .= '<a href="http://'.$data['website'].'">'.$data['website'].'</a><br />';
		}
		$div .= '<p>'.$data['comments'].'</p>'
			.'<div class="small">Posted: '.$data['date'].' | '
			.'<a href="#" onclick="editentry('.$key.');">Edit</a> | '
            .'<a href="#" onclick="deleteentry('.$key.');">Delete</a></div>';
		if($replace == FALSE)
		{
			$div .= '</div>';
		}
		return $div;
	}

	function _checkEmail($email)
	{
		//checks proper syntax
		if(preg_match( '/^[A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i' , $email))
		{
			return true;
		}
		return false;
	}
}
?>
