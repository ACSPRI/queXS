<?php 
/**
 * Functions related to XHTML code generation
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


/**
 * Display a valid XHTML Strict header
 *
 * @param string $title HTML title
 * @param bool $body True if to display the end of the head/body
 * @param bool|array $css False for no CSS otherwise array of CSS include files
 * @param bool|array $javascript False for no Javascript otherwise array of Javascript include files
 * @param string $bodytext Space in the body element: good for onload='top.close()' to close validly
 * @param bool|int $refresh False or 0 for no refresh otherwise the number of seconds to refresh
 * @param bool $clearrefresh False if we want to pass on any GET request in header, True to clear
 * 
 * @see xhtml_foot()
 */
function xhtml_head($title="",$body=true,$css=false,$javascript=false,$bodytext=false,$refresh=false,$clearrefresh=false,$subtitle=false)
{
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head><title><?php  if (empty($title)) print "CATI"; else print "CATI: $title"; ?></title>
<?php 
	if ($css)
		foreach ($css as $c) print "<link rel='stylesheet' href='$c' type='text/css'></link>";
	if ($javascript)
		foreach ($javascript as $j) print "<script type='text/javascript' src='$j'></script>";
	if ($refresh && ALLOW_PAGE_REFRESH)
	{
		print " <!--Set to refresh every $refresh seconds-->
			<meta http-equiv='Cache-Control' content='no-cache'/>
			<meta http-equiv='refresh' content='$refresh";
		if ($clearrefresh) print ";url=?";
		print "'/>";
	}
	if (!$body) return;
?>
  <meta charset="utf-8"/>
	</head>
<?php 
	if ($bodytext) print "<body $bodytext>"; else print "<body>";
	print "<h1 class='header text-primary'>" . "$title" . "&emsp;&emsp;<small class='text-capitalize'>" . "$subtitle" . "</small></h1>"; 
	/* Let's print header that equals to menu item and page title !!!, move previous headers to "subtitles"*/
}

/**
 * Display a valid XHTML Strict footer
 *
 * @see xhtml_head()
 */

function xhtml_foot($javascript = false){		//added javascript files array to the footer 
	if ($javascript)  
		foreach ($javascript as $j) print "<script type='text/javascript' src='$j'></script>";
?>
		<!---  Scroll to Top of the page    -->
	<span class="totop" style="display:none;"><a href=" "><i class="fa fa-3x fa-arrow-circle-o-up"></i></br><?php echo T_("UP");?></a></span> 
	
	</body>
	</html>

<?php 
}

/**
 * Display a valid XHTML Strict table
 *
 * @param array $content Content from database usually an array of arrays
 * @param array $fields The names of fields to display
 * @param bool|array $head False if no header otherwise array of header titles
 * @param string $class Table CSS class
 * @param bool|array $highlight False if nothing to highlight else an array containing the field to highlight
 * @param bool|array $total False if nothing to total else an array containing the fields to total
 * 
 *		AD:>	for  @value    $class = "tclass" added  "Bootstrap" table classes  
 *		AD:> 	added 	@param  string  $id - > to transfer table ID if required 
 *		AD:> 	added 	@param  string  $name - > to transfer table name if required
 */
function xhtml_table($content,$fields,$head = false,$class = "tclass",$highlight=false,$total=false,$id=false,$name=false)
{
	$tot = array();
	if ($class == "tclass") $class = "table-hover table-bordered table-condensed tclass";
	print "<table class=\"$class\" id=\"$id\" name=\"$name\" data-toggle=\"table\" data-toolbar=\"filter-bar\" data-show-filter=\"true\" >";
	if ($head)
	{
		print "<thead class=\"highlight\"><tr>";
		foreach ($head as $e)
			print"<th data-field=\"$e\" data-sortable=\"true\">$e</th>";
		print "</tr></thead>";
	}
	print "<tbody>";
	foreach($content as $row)
	{
		if ($highlight && isset($row[key($highlight)]) && $row[key($highlight)] == current($highlight))
			print "<tr class=\"highlight\">";
		else
			print "<tr>";

		foreach ($fields as $e)
		{
			print "<td>";
			if (isset($row[$e])) print $row[$e];
			print "</td>";
			if ($total && in_array($e,$total))
			{
				if (!isset($tot[$e])) 
					$tot[$e] = 0;
				$tot[$e] += $row[$e];
			}
		}
		print "</tr>";
	}
	if ($total)
	{
		print "</tbody><tfoot><tr>";
		foreach ($fields as $e)
		{
			print "<td><b>";
			if (in_array($e,$total))
				print $tot[$e];
			print "</b></td>";
		}
		print "</tr></tfoot>";
	}
	else{
		print "</tbody>";
	}
	print "</table>";
}


/**
 * Display a drop down list based on a given array
 *
 * Example SQL:
 *  SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 *  FROM questionnaire
 *
 *
 * @param array $elements An array of arrays containing a value and a description and if selected (3 elements)
 * @param string $selectid The ID of the element
 * @param string $var The var name of the return string
 * @param bool $useblank Add a blank element to the start of the list
 * @param string|bool $pass Anything to pass along in the return string (remember to separate with &amp;)
 * @param bool $js Whether to use JS or not
 * @param bool $indiv Whether to display in a div or not
 * @param array|bool $select The element to select manually (element,string) (not using selected=\'selected\' in array)
 * @param bool $print Default is true, print the chooser otherwise return as a string
 *	
 */
function display_chooser($elements, $selectid, $var, $useblank = true, $pass = false, $js = true, $indiv = true, $selected = false, $print = true, $divclass=false, $selectclass="form-control")
{
  $out = "";
	if ($indiv) $out .= "<div class='$divclass'>";
	$out .= "<select id='$selectid' name='$selectid' class='$selectclass'" ;
	if ($js) $out .= "onchange=\"LinkUp('$selectid')\"";
	$out .= ">";
	if ($useblank)
	{
		$out .= "<option value='";
		if ($js) $out .= "?";
		if ($pass != false)
			$out .= $pass;
		$out .= "'></option>";
	}
	foreach($elements as $e)
	{
		if ($js)
		{
			$out .= "<option value='?$var={$e['value']}";
			if ($pass != false)
				$out .= "&amp;$pass";
			$out .= "' ";
		}
		else
		{
			$out .= "<option value='{$e['value']}' ";
		}

		if ($selected == false)
		{
			if (isset($e['selected']))
				$out .= $e['selected']; 
		}
		else
			if (strcmp($selected[1],$e[$selected[0]]) == 0) $out .= "selected='selected'";

		$out .= ">".strip_tags($e['description'])."</option>";
	}
	$out .= "</select>";
  if ($indiv) $out .= "</div>";
  if ($print) 
    print $out;
  else 
    return $out;
}

function xhtml_object($data, $id, $class="embeddedobject")
{
	if (browser_ie())
		print '<iframe class="'.$class.'" id="'.$id.'" src="'.$data.'" frameBorder="0"><p>Error while loading data from  ' . "$data" . ', try with Frefox </p></iframe>';
	else
		print '<object class="'.$class.'" id="'.$id.'" data="'.$data.'" standby="Loading panel..." type="application/xhtml+xml"><p>Error while loading data from  ' . "$data" . ', try with Frefox </p></object>';
}

/**
 * Detect if the user is running on internet explorer
 *
 * @return bool True if MSIE is detected otherwise false
 */
function browser_ie()
{
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
        return true;
    else
        return false;
}
?>