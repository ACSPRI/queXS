<?php
/**
 * Almost real life example, show a list reviews, letting you add one, and then updating the list
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

// include the helper class
require_once 'HTML/AJAX/Helper.php';

// create an instance and set the server url
session_start();
$ajaxHelper = new HTML_AJAX_Helper();
$ajaxHelper->serverUrl = 'auto_server.php';
$ajaxHelper->jsLibraries[] = 'JSON'; // not included in the default list before 0.2.6

?>
<html>
<head>

<?php
    // output a javascript neded to setup HTML_AJAX
    // by default this is all the libraries shipped with HTML_AJAX, take a look at $ajaxHelper->jsLibraries to edit the list
    echo $ajaxHelper->setupAJAX();

    // ajax helper should do this for you but it doesn't yet
?>
<script type="text/javascript" src="auto_server.php?stub=review"></script>

<script type="text/javascript">
var reviewCallback = {
    // after a review we get a chunk of html that we can update the reviewList with
    newReview: function(result) {
        document.getElementById('reviewList').innerHTML += result;
    },
    // after a review is updated we get a chunk of html, replace a node from our lookup list with that
    updateReview: function(result) {
        var newdiv = document.createElement('div');
        newdiv.innerHTML = result[1];
        document.getElementById('reviewList').replaceChild(newdiv.firstChild,reviewCallback.nodeList[result[0]]);
    },
    nodeList: []
}
function sendReview(form) {
    var remoteReview = new Review(reviewCallback);
    var payload = new Object();
    for(var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].name) {
            payload[form.elements[i].name] = form.elements[i].value;
        }
    }

    // do any needed validation here

    remoteReview.newReview(payload);
}

function updateReview(id,form) {
    var remoteReview = new Review(reviewCallback);
    var payload = new Object();
    for(var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].name) {
            payload[form.elements[i].name] = form.elements[i].value;
        }
    }

    // do any needed validation here

    remoteReview.updateReview(id,payload);
}


function editReview(id,node) {
    var form = document.getElementById('reviewForm').cloneNode(true);
    form.onsubmit = function() { updateReview(id,this); return false; }

    var data = new Object();
    var divs = node.getElementsByTagName('div');
    for(var i = 0; i < divs.length; i++) {
        if (divs[i].className) {
            data[divs[i].className] = divs[i].innerHTML;
        }
    }

    for(var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].name) {
            form.elements[i].value = data[form.elements[i].name];
        }
    }

    document.getElementById('reviewList').replaceChild(form,node);
    reviewCallback.nodeList[id] = form;
}
</script>

<style type="text/css">
.name {
    margin-top: 5px;
    background-color: #ddd;
}
#reviewList {
    width: 300px;
}
</style>

</head>
<body>

<h2>Add a new Review</h2>
<form id="reviewForm" onsubmit="sendReview(this); return false;" style="border: dotted 1px black">

Your Name: <input name="name"><br>
Your Review: <textarea name="review"></textarea><br>
<input type="submit">
</form>

<div id="reviewList">
<?php
    if (isset($_SESSION['reviews'])) {
        foreach($_SESSION['reviews'] as $key => $review) {
            echo "<div onclick='editReview($key,this)'><div class='name'>$review->name</div><div class='review'>$review->review</div></div>\n";
        }
    }
?>
</div>
</body>
</html>
<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
