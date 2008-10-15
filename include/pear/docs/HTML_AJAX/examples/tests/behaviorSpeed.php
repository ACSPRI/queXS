<html>
<head>
<title>Behavior Speed Test</title>
<script type="text/javascript" src="../server.php?client=util,main,alias,behavior"></script>
<script type="text/javascript">
Behavior.debug = 'debug';
Behavior.register('.one',function(element) {
		element.style.color = 'red';
	}
);
Behavior.register('.two',function(element) {
		element.style.color = 'blue';
	}
);
Behavior.register('.three',function(element) {
		element.style.color = 'green';
	}
);
Behavior.register('.four',function(element) {
		element.style.color = 'orange';
	}
);
Behavior.register('.five',function(element) {
		element.style.color = 'purple';
	}
);
</script>
</head>
<body>
<p>This page is used to test the speed of HTML_AJAX's JavaScript Behavior support.</p>

<pre id="debug">
</pre>

<?php
$classes = array('one','two','three','four','five');
for($i = 0; $i < 100; $i++) {
	$class = $classes[array_rand($classes)];

	echo "<div class='$class' id='el$i'>$class";
	for($b = 0; $b < 50; $b++) {
		$c = $classes[array_rand($classes)];
		echo "<span class='$c' id='el$i$b'>$c</span>";
	}
	echo "</div>\n";
}
?>
</body>
</html>
