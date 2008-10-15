<?php
/**
 * HTML_AJAX_Util.varDump() examples
 * 
 * @category   HTML
 * @package    AJAX
 * @author     Arpad Ray <arpad@php.net>
 * @copyright  2005 Arpad Ray
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

?>
<html>
<head>
<script type="text/javascript" src="../server.php?client=util"></script>
<script type="text/javascript">
function foo() {
    this.bar = "baz";
    this.bat = 5;
}
var obj = new foo();

var a = [
    null,
    true,
    13,
    1.337,
    'foo',
    [1, 2, 3],
    [1, [1, 2, 3], 3],
    obj
];
    
    
function dotest() {
    var foo = document.getElementById("foo");

    for (ak in a) {
        foo.innerHTML += "<pre>" + HTML_AJAX_Util.varDump(a[ak], 1) + "</pre><br>";
    }    
}
    
</script></head><body onload="dotest()">

<hr>
PHP:
<hr>
<div>
<?php
        
class foo {
    var $bar = 'baz';
    var $bat = 5;
}
$obj = new foo;

$a = array(
    null,
    true,
    13,
    1.337,
    'foo',
    array(1, 2, 3),
    array(1, array(1, 2, 3), 3),
    $obj
);

foreach ($a as $v) {
    echo "<pre>";
    var_dump($v);
    echo "</pre>";
}

?>
</div>
<hr>
Javascript:
<hr>
<div id="foo">
</div>
<hr>
Source:
<hr>
<div>
        <?php show_source(__FILE__); ?>
</div>
</body>
</html>
