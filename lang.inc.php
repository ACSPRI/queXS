<?php 
/**
 * Language configuration file
 *
 *
 *	This file is part of queXS
 *	
 *	queXS is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *	
 *	queXS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	along with queXS; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @author Adam Zammit <adam.zammit@deakin.edu.au>
 * @copyright Deakin University 2007,2008
 * @package queXS
 * @subpackage configuration
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


include_once(dirname(__FILE__).'/config.inc.php');

/**
 * The phpgettext package
 */
require_once(dirname(__FILE__).'/include/php-gettext-1.0.11/gettext.inc');

/**
 * Translate the given elements of the array
 *
 * @param array The array to translate
 * @param array The elements in the array to translate
 * @return The array with the elements translated
 */
function translate_array(&$a,$b)
{
	foreach ($a as &$row)
		foreach($b as $el)
			if (isset($row[$el])) $row[$el] = T_($row[$el]);
}

/**
 * Translate then quote a string to make it ready
 * for database insertion
 *
 * @param string $msg The message to translate and quote
 * @return string The translated message quoted
 */
function TQ_($msg)
{
  $msg = T_($msg);
  $msg = str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$msg);
  return str_replace("'","\\'",$msg);
}

$locale = DEFAULT_LOCALE;
if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
{
  $l = explode(",",$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
  foreach($l as $ls)
  {
    $ls = strtolower($ls);
    if (file_exists(dirname(__FILE__)."/locale/".$ls))
    {
      $locale = $ls;
      break;
    }
    else if (file_exists(dirname(__FILE__)."/locale/". substr($ls,0,2)))
    {
      $locale = substr($ls,0,2);
      break;
    }
  }
}
T_setlocale(LC_MESSAGES, $locale);
T_bindtextdomain($locale,  dirname(__FILE__)."/locale");
T_bind_textdomain_codeset($locale, 'UTF-8');
T_textdomain($locale);

?>
