<?php
/**
 * Using the ordered queue to deal with high latency
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
session_start();
if (isset($_GET['latency'])) {
    $_SESSION['sleep'] = $_GET['latency'];
}
?>
<html>
<head>

<script type='text/javascript' src="server.php?client=Util,main,dispatcher,httpclient,request,json,loading,iframe,orderedqueue"></script>
<script type='text/javascript' src="slow_server.php?stub=livesearch"></script>

</head>
<body>
<script type="text/javascript">

// callback hash, outputs the results of the search method
callback = {
    search: function(result) {  
        var out = "<ul>";

        // if we have object this works right, if we have an array we get a problem
        // if we have sparse keys will get an array back otherwise will get an array
        for(var i in result) {
            if (i != '______array') {
                out += "<li>"+i+" = "+result[i]+"</li>";
            }
        }
        out += "</ul>";
        document.getElementById('target').innerHTML = out;
    }
}

// setup our remote object from the generated proxy stub
var remoteLiveSearch = new livesearch(callback);

// we could change the queue by overriding the default one, but generally you want to create a new one
// set our remote object to use the ordered queue
remoteLiveSearch.dispatcher.queue = 'ordered';

HTML_AJAX.queues['ordered'] = new HTML_AJAX_Queue_Ordered();


// what to call on onkeyup, you might want some logic here to not search on empty strings or to do something else in those cases
function searchRequest(searchBox) {
    remoteLiveSearch.search(searchBox.value);
}
</script>

<p>This is a really basic LiveSearch example, type in the box below to find a fruit</p>
<p>By deafult server add latency to the request, starting with 5 seconds and reducing 1 second per request</p>
<p>An ordered queue has been used which should make results come back in the order they are sent not the order they are received</p>

<form action="slow_livesearch.php">
<label>Set current latency too: <input name="latency" size=4><input type="submit">
</form>
<hr><br>

Search <input id="search" autocomplete="off" onkeyup="searchRequest(this)">

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>
</body>
</html>
<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
