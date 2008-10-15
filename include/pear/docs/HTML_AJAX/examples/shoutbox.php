<?php
/**
 * AJAX form submission, practical example
 *
 * @category   HTML
 * @package    AJAX
 * @author     Arpad Ray <arpad@php.net>
 * @copyright  2005 Arpad Ray
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

define('MESSAGE_FILE', 'messages.txt');
$messages = (is_readable(MESSAGE_FILE) ? unserialize(file_get_contents(MESSAGE_FILE)) : array());

function show_messages()
{
    foreach ($GLOBALS['messages'] as $m) {
        echo "<br>Name:{$m[0]}<br>Message:{$m[1]}<br><hr>";
    }
}

function save_messages()
{
    if ($h = @fopen(MESSAGE_FILE, 'wb')) {
        $GLOBALS['messages'] = array_slice($GLOBALS['messages'], -5);
        fwrite($h, serialize($GLOBALS['messages']));
        fclose($h);
    }
}

if (!empty($_POST)) {

    if (!empty($_POST['name']) && !empty($_POST['message'])) {
        $messages[] = array(
            htmlspecialchars(strip_tags(substr($_POST['name'], 0, 10))),
            htmlspecialchars(strip_tags(substr($_POST['message'], 0, 30)))
        );
        save_messages();
    }
    show_messages();
    exit;
}

?><html>
    <head>
        <script type="text/javascript" src="server.php?client=all&amp;stub=all"></script>
    </head>
    <body>
        <h1>Messages:</h1>
        <div id="target">
            <?php show_messages(); ?>
        </div>
        <form action="shoutbox.php" method="post" onsubmit="return !HTML_AJAX.formSubmit(this, 'target')">
            <label>
                Name:
                <input type="text" name="name" id="name" />
            </label>
            <label>
                Message:
                <input type="text" name="message" id="message" />
            </label>
            <input type="submit" value="Submit" />
        </form>    
    </body>
</html>
