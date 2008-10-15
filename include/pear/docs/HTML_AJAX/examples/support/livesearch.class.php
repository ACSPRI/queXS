<?php
/**
 * Simple test class for doing fake livesearch
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
class livesearch {
	/**
	 * Items to search against
	 */
	var $livesearch = array(
		'Orange',
		'Apple',
		'Pear',
		'Banana',
		'Blueberry',
		'Fig',
		'Apricot',
		'Cherry',
		'Peach',
		'Plum',
		'Nectarine',
		'Boysenberry',
		'Cranberry',
		'Blackberry',
		'Clementine',
		'Grapefruit',
		'Lemon',
		'Lime',
		'Tangerine'
		);
	
	/**
	 * Perform a search
	 *
	 * @return array
	 */
	function search($input="") {
		$ret = array();
		if(empty($input)) {
			return $ret;
		}
		if (isset($_SESSION['sleep'])) {
			$ret['Latency Added'] = $_SESSION['sleep'];
		}
		foreach($this->livesearch as $key => $value) {
			if (stristr($value,$input)) {
				$ret[$key] = $value;
			}
		}
		return $ret;
	}
}
?>
