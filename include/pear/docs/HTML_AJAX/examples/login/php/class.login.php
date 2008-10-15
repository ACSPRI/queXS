<?php
	// Includes
	require_once 'HTML/AJAX/Action.php';

	// Error messages
	define('ERR_USERNAME_EMPTY', 'You forgot to enter a username, please try again');
	define('ERR_USERNAME_INVALID', 'The username you entered is invalid. Please try "peter" (without quotes)');
	define('ERR_PASSWORD_EMPTY', 'You forgot to enter a password, please try again');
	define('ERR_PASSWORD_INVALID', 'The password you entered is invalid. Please try "gabriel" (without quotes)');
	define('ERR_EMAIL_EMPTY', 'You forgot to enter an e-mail address');
	define('ERR_EMAIL_INVALID', 'The e-mail address you entered is invalid. Please enter a valid e-mail address.');

	/**
	 * Login class used in the "login form" example
	 * Please note: Constructors and private methods marked with _ are never exported in proxies to JavaScript
	 *
	 * @category   HTML
	 * @package    AJAX
	 * @author     Gilles van den Hoven <gilles@webunity.nl>
	 * @copyright  2005 Gilles van den Hoven
	 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
	 * @version    Release: 0.5.2
	 * @link       http://pear.php.net/package/HTML_AJAX
	 */
	class login {
		/**
		 * PHP5 requires a constructor
		 */
		function login() {
		}

		/**
		* Checks the proper syntax
		*/	
		function _checkEmail($strEmail) {
			return (preg_match( '/^[A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i' , $strEmail));
		}

		/**
		 * Checks if the passed values are correct.
		 *
		 * @param array the form object
		 */
		function validate($arrayForm) {
			//--------------------------------------------------
			// Initialize function
			//--------------------------------------------------
			// Array to hold the messages
			$arrMessages = array();

			// Set all form values that they validated. Could be improved by analyzing
			// the values passed in $objForm and setting values accordingly.
			$arrValidated = array();
			$arrValidated['username'] = true;
			$arrValidated['password'] = true;
			$arrValidated['email'] = true;

			// Never trust the values passed by users :)
			$objForm = new stdClass();
			$objForm->username = trim($arrayForm['username']);
			$objForm->password = trim($arrayForm['password']);
			$objForm->email = trim($arrayForm['email']);

			//--------------------------------------------------
			// Check values
			//--------------------------------------------------
			// Check username
			if ($objForm->username == '') {
				$arrMessages[] = ERR_USERNAME_EMPTY;
				$arrValidated['username'] = false;
			} else if ($objForm->username != 'peter') {
				$arrMessages[] = ERR_USERNAME_INVALID;
				$arrValidated['username'] = false;
			}

			// Check password
			if ($objForm->password == '') {
				$arrMessages[] = ERR_PASSWORD_EMPTY;
				$arrValidated['password'] = false;
			} else if ($objForm->password != 'gabriel') {
				$arrMessages[] = ERR_PASSWORD_INVALID;
				$arrValidated['password'] = false;
			}

			// Check email
			if ($objForm->email == '') {
				$arrMessages[] = ERR_EMAIL_EMPTY;
				$arrValidated['email'] = false;
			} else if ($this->_checkEmail($objForm->email) == false) {
				$arrMessages[] = ERR_EMAIL_INVALID;
				$arrValidated['email'] = false;
			}

			//--------------------------------------------------
			// Finalize function
			//--------------------------------------------------
			// Create the message list
			$strMessages = '';
			if (count($arrMessages) > 0) {
				$strMessages = '<ul>';
				foreach ($arrMessages as $strTemp) {
					$strMessages.= '<li>'.$strTemp.'</li>';
				}
				$strMessages.= '</ul>';
			}

			// Create a response object
			$objResponse = new HTML_AJAX_Action();

			// Assign the messages
			$objResponse->assignAttr('messages', 'innerHTML', $strMessages);
			$objResponse->insertScript("toggleElement('messages', ".(($strMessages != '') ? 1 : 0).");");

			// Generate the scripts to update the form elements' label and input element
			foreach ($arrValidated as $strKey => $blnValue) {
				$objResponse->insertScript("setElement('".$strKey."', ".(($blnValue) ? '1' : '0').");");
			}

			// Test for no messages
			if ($strMessages == "") {
				$objResponse->insertAlert("Well done chap!");
			}

			// And ready! :)
			return $objResponse;
		}
	}
?>
