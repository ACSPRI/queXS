<script type="text/javascript" src="../server.php?client=phpSerializer,util"></script>
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

echo '<h1><a name="pos">Positives</a> | <a href="#neg">Negatives</a></h1>';
$c = count($examples);
for ($i = 0; $i < $c; $i++) {
    echo "<strong>PHP Code:</strong>\n<pre>$examples[$i]</pre>\n<strong>PHP value:</strong><pre>\n";
    eval($examples[$i]);
    var_dump($foo);
    $sfoo = serialize($foo);
    echo "</pre>\n<strong>Serialized in PHP:</strong>\n<pre>", $sfoo, "</pre>\n",
         "<strong>Unserialized in JS:</strong>\n<pre>\n",
         '<script type="text/javascript">',
         'var jsr = new HTML_AJAX_Serialize_PHP();',
         'var sfoo = unescape("', urlencode($sfoo), '"); var usfoo = jsr.unserialize(sfoo); if (jsr.error) {',
         'document.write("Error: " + jsr.getError() + "\n"); } document.write(HTML_AJAX_Util.varDump(usfoo) + ',
         '"</pre>\n<strong>Serialized in JS:</strong>\n<pre>" + jsr.serialize(usfoo));', "</script>\n</pre>\n<hr />\n\n";
}

$bad_examples = array(
    'x',
    'x:1',
    'N',
    'Nx',
    'b:f;',
    'b:1',
    'i:foo;',
    'i:1',
    'd:foo;',
    'd:1.1.1;',
    'd:1.1',
    's:6:"foo";',
    's:6:"foofoo"',
    's:1:"foo";',   
    's:0:""',
    'a:3:{s:1:"aa";i:1;s:1:"b";i:2;i:0;i:3;}',
    'a:4:{s:1:"a";i:1;s:1:"b";i:2;i:0;i:3;',
    'a:3:{i:1;s:1:"b";i:2;i:0;i:3;}',
    'a:3:{}',
    'O:3:"Fooo":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":3:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}',
    'O:3:"Foo":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe"}}'
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

$good_objects = array(
    'O:3:"Foo":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:3:"foo";s:87:"O:3:"Bar":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:3:"foo";s:90:"hi"O:3:"Bar":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}'
);

$bad_objects = array(
    'O:6:"stdClass":2:{s:3:"foo";s:5:"hello";s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:3:"foo";O:8:"stdClass":1:{s:3:"bar";s:2:"hi";}s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}',
    'O:3:"Foo":2:{s:6:"hi"foo";O:8:"stdClass":1:{s:3:"bar";s:2:"hi";}s:3:"bar";a:2:{i:0;s:5:"world";i:1;s:8:"universe";}}'
);

include('HTML/AJAX/Serializer/PHP.php');
$sr = new HTML_AJAX_Serializer_PHP;

$allowedClasses = array('Foo');

echo '<h1><a name="opos">Object Positives</a> | <a href="#oneg">Object Negatives</a></h1>';
foreach ($good_objects as $sfoo) {
    echo "</pre>\n<strong>Serialized in PHP:</strong>\n<pre>", $sfoo, "</pre>\n",
        "<strong>Class Names</strong><pre>\n", implode(', ', $sr->_getSerializedClassNames($sfoo)),
        "</pre><strong>Unserialized in PHP:</strong>\n<pre>\n";
    var_dump($sr->unserialize($sfoo, $allowedClasses));
    echo "</pre>\n<hr />\n\n";
}

echo '<h1><a href="#opos">Object Positives</a> | <a name="oneg">Object Negatives</a></h1>';
foreach ($bad_objects as $sfoo) {
    echo "</pre>\n<strong>Serialized in PHP:</strong>\n<pre>", $sfoo, "</pre>\n",
        "<strong>Class Names</strong><pre>\n", implode(', ', $sr->_getSerializedClassNames($sfoo)),
        "</pre><strong>Unserialized in PHP:</strong>\n<pre>\n";
    var_dump($sr->unserialize($sfoo, $allowedClasses));
    echo "</pre>\n<hr />\n\n";
}



?>
