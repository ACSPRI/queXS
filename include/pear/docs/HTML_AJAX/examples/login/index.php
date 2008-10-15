<?php
	require_once 'HTML/AJAX/Helper.php';
	$objAjaxHelper = new HTML_AJAX_Helper();
	$objAjaxHelper->serverUrl = './php/auto_server.php';
	$objAjaxHelper->jsLibraries[] = 'haserializer';
	$objAjaxHelper->stubs[] = 'login';
	$strAjaxScript = $objAjaxHelper->setupAJAX();
?>
<html>
<head>
	<title>Form validation (v2) with HTML_AJAX</title>
	<!-- HTML_AJAX -->
	<?php echo $strAjaxScript ?>
	<script type="text/javascript">
		/**
		 * Basic page initialization
		 */
		function initPage() {
			// Set up the labels so they know the associated input elements
			var arrLabels = document.getElementsByTagName("label");
			for (var i=0; i < arrLabels.length; i++) {
				var objTemp = arrLabels[i];
				var strFor = objTemp.getAttribute('for');

				// Fix the attributes
				if (strFor != '') {
					// Set the ID of the label
					objTemp.setAttribute('id', 'l' + strFor);

					// Save the original class of the label (if any)
					objTemp.setAttribute('classOrig', objTemp.getAttribute('class'));
				}
			}

			// Set the focus on the first element
			document.getElementById('username').focus();
		}

		/**
		 * Sets the class of an element (build for this example)
		 */
		function setElement(strElement, blnValidated) {
			// Update the label
			var objElem = document.getElementById('l' + strElement);
			if (objElem) {
				if (blnValidated == 1) {
					strClass = objElem.getAttribute('classOrig');
				} else {
					strClass = 'error';
				}
				objElem.setAttribute('class', '' + strClass);
			}

			return false;
		}

		/**
		 * Shows or hides an element
		 */
		function toggleElement(strElement, blnVisible) {
			var objStyle = document.getElementById(strElement).style;
			if (objStyle) {
				objStyle.display = (blnVisible == 1) ? 'block' : 'none';
			}
		}

		/**
		 * Login function
		 */
		function doLogin() {
			// Create object with values of the form
		    var objTemp = new Object();
		    objTemp['username'] = document.getElementById('username').value;
		    objTemp['password'] = document.getElementById('password').value;
		    objTemp['email'] = document.getElementById('email').value;

			// Create a dummy callback so the loading box will appear...
			var objCallback = { validate: function() {} };

			// Send the object to the remote class
		    var objLogin = new login(objCallback);
		    objLogin.validate(objTemp);
		}
	</script>

	<!-- STYLESHEET  -->
	<link rel="stylesheet" type="text/css" href="login.css" />
</head>
<body>
	<!-- THE ERROR MESSAGES -->
	<div id="messages" class="errorbox"></div>
	<br />

	<!-- THE FORM -->
	<form action="" method="post" onSubmit="doLogin(); return false;">
		<fieldset style="width: 500px;">
			<legend>Enter your login details</legend>

			<table width="400" border="0" cellspacing="0" cellpadding="2">
				<tr>
					<td><label for="username">Username:</label></td>
					<td><input type="text" name="username" id="username" size="40" tabindex="1"></td>
				</tr>
				<tr>
					<td><label for="password">Password:</label></td>
					<td><input type="text" name="password" id="password" size="40" tabindex="2"></td>
				</tr>
				<tr>
					<td><label for="email">E-mail:</label></td>
					<td><input type="text" name="email" id="email" size="40" tabindex="3"></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" value="  login  ">&nbsp;<input type="reset" value="  reset  "></td>
				</tr>
			</table>
		</fieldset>
	</form>
	<div id="HTML_AJAX_LOADING" class="AjaxLoading">Please wait while loading</div>

	<p>
		<strong>This sample is an updated version of the original login sample</strong>:<br />
		&#0187; It now uses the new HTML_AJAX_Action class.<br>
		&#0187; The initialization of the labels and their input elements is done by Javascript to clean up the page (function: initPage()).<br>
		<br>

		<strong>Design notes</strong>:<br>
		&#0187; The attribute &quot;style.display&quot; cannot be set from php code. Don't know why though. I created a wrapper function for it called &quot;toggleElement&quot; which does the trick. Funny enough when i execute thesame lines of script using -&gt;insertScript nothing happens.<br>
		&#0187; You have to add a dummy callback in order to show the loading progress bar.<br>
		&#0187; Enter username &quot;peter&quot;, password &quot;gabriel&quot; and any valid e-mail adress to see a messagebox.<br>

		<br>
		&copy; under LGPL @ 21 nov 2005 by www.webunity.nl<br>
	</p>

	<script type="text/javascript">
		if (document.getElementsByTagName) {
			initPage();
		} else {
			alert('This sample requires the DOM2 "getElementsByTagName" function.');
		}
	</script>
</body>
</html>
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
