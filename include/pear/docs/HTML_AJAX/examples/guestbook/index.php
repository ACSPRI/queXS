<?php
/**
 * A simple guestbook with the goal of not a line of javascript :)
 *
 * @category   HTML
 * @package    AJAX
 * @author     Elizabeth Smith <auroraeosrose@gmail.com>
 * @copyright  2005 Elizabeth Smith
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

//require the helper class - it will take care of everything else
require_once 'HTML/AJAX/Helper.php';

//since we're not REALLY using a backend like a database, use sessions to store data
session_start();
//set up HTML_AJAX_Helper
$ajaxHelper = new HTML_AJAX_Helper();
//tell it what url to use for the server
$ajaxHelper->serverUrl = 'auto_server.php';
//add haserializer to set
$ajaxHelper->jsLibraries[] = 'haserializer';
// Open tags problem... I know ugly.
$ajaxHelper->stubs[] = 'guestbook';

print '<?xml version="1.0" encoding="utf-8"?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<title>My Guestbook</title>
<?php
    // output a javascript neded to setup HTML_AJAX
    echo $ajaxHelper->setupAJAX();
?>
<script type="text/javascript">
HTML_AJAX.onError = function(e) { alert(HTML_AJAX_Util.quickPrint(e)); }
</script>
<style type="text/css">
body {
color: #24006B;
}
h2 {
  text-align: center;
  color: #330099;
  }
#guestbookForm, #guestbookList {
  width: 65%;
  margin-right: auto;
  margin-left: auto;
  padding: 1.5em;
  margin-top: 10px;
  background-color: #D5BFFF;
  border: 4px double #FFCC00;
}
fieldset, div.entry {
background-color: #FFF2BF;
border: 4px double #330099;
padding: 0.5em;
}
legend {
color: #330099;
font-size: 0.8em;
font-style: italic;
}
label {
clear: both;
display: block;
float: left;
width: 20%;
text-align: right;
font-weight: bold;
}
input {
display: block;
float: left;
width: 40%;
margin: 0 0.5em 0.5em 0.5em;
background-color: #B38F00;
border: 1px solid #AA80FF;
color: #330099;
}
input:focus, textarea:focus {
background-color: #D5BFFF;
border: 1px solid #B38F00;
}
textarea {
display: block;
float: left;
width: 40%;
height: 10em;
margin: 0 0.5em 0.5em 0.5em;
background-color: #B38F00;
border: 1px solid #AA80FF;
color: #330099;
}
input[type="submit"] {
display: block;
width: auto;
float: none;
margin-right: auto;
margin-left: auto;
font-size: 1.5em;
font-weight: bold;
background-color: #330099;
color: #FFCC00;
border: 3px double #FFE680;
}
.error {
color: #CC0000;
font-weight: bold;
float: left;
}
.small {
font-size: 0.8em
}
</style>

</head>
<body>
<?php //eventually ajax_helper should set this up for you, it's not done yet?>
<script type="text/javascript">
function sendguestbook(form) {
    var remoteguestbook = new guestbook();
    var payload = new Object();
    for(var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].name) {

            payload[form.elements[i].name] = form.elements[i].value;
        }
    }
    remoteguestbook.newEntry(payload);
    return false;
}
function clearguestbook() {
    var remoteguestbook = new guestbook();
    remoteguestbook.clearGuestbook();
}
function deleteentry(id) {
    var remoteguestbook = new guestbook();
    remoteguestbook.deleteEntry(id);
}
function editentry(id) {
    var remoteguestbook = new guestbook();
    remoteguestbook.editEntry(id);
}
function updateselect(id) {
    var remoteguestbook = new guestbook();
    remoteguestbook.updateSelect(id);
}
</script>
<h2>Welcome to the Guestbook</h2>
<div id="guestbookList">
<?php

    if (isset($_SESSION['entries'])) {
        foreach($_SESSION['entries'] as $key => $data) {
           echo '<div class="entry" id="entry'.$key.'">'
			.'<h3><a href="mailto:'.$data->email.'">'.$data->name.'</a></h3>';
            if(!empty($data->website))
            {
                echo '<a href="http://'.$data->website.'">'.$data->website.'</a><br />';
            }
			echo '<p>'.$data->comments.'</p>'
            .'<div class="small">Posted: '.$data->date.' | '
            .'<a href="#" onclick="editentry('.$key.');">Edit</a> | '
            .'<a href="#" onclick="deleteentry('.$key.');">Delete</a></div>'
			.'</div>';
        }
    }
?>
</div>

<div><a href="#" onclick="clearguestbook(this);">Clear Guestbook</a></div>
 <form id="guestbookForm" action="index.php" method="post" onsubmit="sendguestbook(this); return false;">
  <fieldset>
   <legend>Leave Your Comments</legend>
    <label for="name">Name: </label><input name="name" id="name" />
    <label for="email">Email: </label><input name="email" id="email" />
    <label for="website">Website: </label><input name="website" id="website" />
    <label for="comments">Comments: </label><textarea name="comments" id="comments"></textarea>
    <br style="clear: both" />
    <input type="submit" id="submit" name="submit" value="Add Comments" />
  </fieldset>
 </form>

<p>Fill a select item with a list of options - tests the HTML_AJAX_Action::replaceNode and HTML_AJAX_Action::createNode methods</p>
 <form id="testing" action="index.php" method="post" onsubmit="return false;">
  <div>
	<a href="#" onclick="updateselect('replaceme');">Gimme some options</a>
    <select id="replaceme">
		<option name="dog" id="dog">Dog</option>
	</select>
  </div>
 </form>

</body>
</html>
