<?php
/**
 * Simple grab example
 *
 * @category   HTML
 * @package    AJAX
 * @author     Arpad Ray <arpad@php.net>
 * @copyright  2005 Arpad Ray
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

include 'HTML/AJAX.php';

if (isset($_GET['grab'])) {
    die('Grabbed from php!');
}

$ajax = new HTML_AJAX();
if ($ajax->handleRequest()) {
    exit;
}

?><html>
<head>
<script type='text/javascript' src="../js/HTML_AJAX.js"></script>
<script type="text/javascript">

function grab()
{
    var callback = function(result) {
        document.getElementById('target').innerHTML = result;
    }
    HTML_AJAX.grab('grab.php?grab=1', callback);
}

</script>
</head>
<body>
<a href="javascript:grab()">grab</a>
<pre id="target">
</pre>
</body>
</html>
