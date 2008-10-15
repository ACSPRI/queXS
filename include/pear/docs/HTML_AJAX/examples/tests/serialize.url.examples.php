<?php
/**
 * Test cases for the UrlSerializer
 */
?>
<script type="text/javascript" src="../server.php?client=UrlSerializer,util"></script>
<script type="text/javascript">

</script>
<?php

$examples = array(
    '$foo = null;',
    '$foo = true;',
    '$foo = "foobar";',
    '$foo = 337;',
    '$foo = 99.99;',
    '$foo = array("a" => 1, "b" => 2, 3);',
    '$foo = array(1,2,array(1,2,3));',
    'class Foo { var $foo; var $bar; }' 
    . '$foo = new Foo; $foo->foo = "hello"; $foo->bar = array("world","universe");'
);

require_once 'HTML/AJAX/Serializer/Urlencoded.php';
$sr = new HTML_AJAX_Serializer_Urlencoded;
echo '<h1><a name="pos">Positives</a> | <a href="#neg">Negatives</a></h1>';
$c = count($examples);
for ($i = 0; $i < $c; $i++) {
    echo "<strong>PHP Code:</strong>\n<pre>$examples[$i]</pre>\n<strong>PHP value:</strong><pre>\n";
    eval($examples[$i]);
    var_dump($foo);
    $sfoo = $sr->serialize($foo);
    echo "</pre>\n<strong>Unserialized in PHP:</strong>\n<pre>";
    var_dump($sr->unserialize($sfoo));
    echo "</pre>\n<strong>Unserialized in JS:</strong>\n<pre>\n",
         '<script type="text/javascript">',
         'var jsr = new HTML_AJAX_Serialize_Urlencoded();',
         'var sfoo = unescape("', urlencode($sfoo), '"); var usfoo = jsr.unserialize(sfoo); if (jsr.error) {',
         'document.write("Error: " + jsr.getError() + "\n"); } document.write(HTML_AJAX_Util.varDump(usfoo) + ',
         '"</pre>\n<strong>Serialized in PHP:</strong>\n<pre>', $sfoo, '</pre>\n',
         '\n<strong>Serialized in JS:</strong>\n<pre>" + jsr.serialize(usfoo));',
         "\n</script>\n</pre>\n<hr />\n\n";
}

$bad_examples = array(
    'x',
    'x-1',
    'x=1x=2',
    'x=1&',
    'x[=]1',
    '[]x=1',
    '_HTML_AJAX]]=1',
    '_HTML_AJAX[[=1',
    '_HTML_AJAX][=1',
    '_HTML_AJAX[=]1',
    '_HTML_AJAX[=1]',
    '_HTML_AJAX[0]=1&_HTML_AJAX]]=1',
    '_HTML_AJAX[0[1]]=1',
    '_HTML_AJAX[0[1]=1'
);

echo '<h1><a href="#pos">Positives</a> | <a name="neg">Negatives</a></h1>';
foreach ($bad_examples as $sfoo) {
    echo "</pre>\n<strong>Invalidly serialized:</strong>\n<pre>", $sfoo, "</pre>\n",
         "<strong>Unserialized in JS:</strong>\n<pre>\n",
         '<script type="text/javascript">',
         'var sfoo = unescape("', urlencode($sfoo), '"); var usfoo = jsr.unserialize(sfoo); if (jsr.error) {',
         'document.write("Error: " + jsr.getError() + "\n"); } document.write(HTML_AJAX_Util.varDump(usfoo));',
         "</script>\n</pre>\n<hr />\n\n";
}


?>
