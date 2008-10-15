<?php
/**
 * Example of Using HTML_AJAX with a queue
 *
 * When using HTML_AJAX your actually always using a queue, its just its a simple one that always performs whatever actions its given immediately
 * HTML_AJAX however provides other queues offering different modes of operation
 *
 * Currently the Queues are (class names are prefixed with: HTML_AJAX_Queue_):
 * Immediate - Default make the request immediately
 * Interval_SingleBuffer - Make request on an interval with holding just the last request between calls
 *
 * Interval_SingleBuffer is generally used for Live Search/Google Suggest type operations
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
?>
<html>
<head>

<script type='text/javascript' src="server.php?client=Util,main,dispatcher,httpclient,request,json,loading,iframe,queues"></script>
<script type='text/javascript' src="auto_server.php?stub=livesearch"></script>

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
// set our remote object to use the rls queue
remoteLiveSearch.dispatcher.queue = 'rls';

// create the rls queue, with a 350ms buffer, a larger interval such as 2000 is useful to see what is happening but not so useful in real life
HTML_AJAX.queues['rls'] = new HTML_AJAX_Queue_Interval_SingleBuffer(350);


// what to call on onkeyup, you might want some logic here to not search on empty strings or to do something else in those cases
function searchRequest(searchBox) {
    remoteLiveSearch.search(searchBox.value);
}
</script>

<p>This is a really basic LiveSearch example, type in the box below to find a fruit</p>

Search <input id="search" autocomplete="off" onkeyup="searchRequest(this)">

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>
</body>
</html>
<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
