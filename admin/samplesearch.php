<?
/**
 * Select and search within a sample to see what case(s) is/are assigned to a sample record
 * and if so to look at them, otherwise give the option to remove a sample record
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
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
 */

/**
 * Configuration file
 */
include("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");


/**
 * Input functions
 */
include("../functions/functions.input.php");

global $db;


if (isset($_GET['sample_id']))
{
	//need to remove this sample record from the sample

	$sample_id = bigintval($_GET['sample_id']);

	$db->StartTrans();

	$sql = "DELETE FROM sample_var
		WHERE sample_id = '$sample_id'";

	$db->Execute($sql);

	$sql = "DELETE FROM sample
		WHERE sample_id = '$sample_id'";

	$db->Execute($sql);

	$db->CompleteTrans();
}


$sample_import_id = false;
if (isset($_GET['sample_import_id'])) 	$sample_import_id = bigintval($_GET['sample_import_id']);

xhtml_head(T_("Search sample"),true,array("../css/table.css"),array("../js/window.js"));
print "<h1>" . T_("Select a sample from the list below") . "</h1>";

$sql = "SELECT sample_import_id as value,description, CASE WHEN sample_import_id = '$sample_import_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM sample_import";

$r = $db->GetAll($sql);

if(!empty($r))
	display_chooser($r,"sample_import_id","sample_import_id");

if ($sample_import_id != false)
{
	if (isset($_GET['search']))
	{
		$search = $db->qstr($_GET['search']);

		$sql = "SELECT sv.sample_id, CASE WHEN c.case_id IS NULL THEN CONCAT('<a href=\'?sample_import_id=$sample_import_id&amp;sample_id=', sv.sample_id , '\'>" . T_("No cases yet assigned: Delete this sample record") . "</a>') ELSE CONCAT('<a href=\'supervisor.php?case_id=', c.case_id , '\'>" . T_("Assigned to questionnaire: ") . "', q.description, '</a>') END as link
			FROM sample_var AS sv
			JOIN (sample as s) ON (s.import_id = '$sample_import_id' and sv.sample_id = s.sample_id)
			LEFT JOIN (`case` AS c, questionnaire AS q) ON ( c.sample_id = sv.sample_id AND q.questionnaire_id = c.questionnaire_id )
			WHERE sv.val LIKE $search
			GROUP BY s.sample_id";

		$r = $db->GetAll($sql);

		if (empty($r))
			print "<p>" . T_("No records in this sample match this search criteria") . "</p>";
		else
		{
			//add sample information to results
			$sql = "SELECT var
				FROM sample_var
				WHERE sample_id = {$r[0]['sample_id']}";

			$rs = $db->GetAll($sql);

			$fnames = array("sample_id");
			$fdesc = array(T_("Sample id"));

			foreach($rs as $rsw)
			{
				$fnames[] = $rsw['var'];
				$fdesc[] = $rsw['var'];
			}

			$fnames[] = "link";
			$fdesc[] = T_("Link");

			foreach($r as &$rw)
			{
				$sql = "SELECT var,val
					FROM sample_var
					WHERE sample_id = {$rw['sample_id']}";

				$rs = $db->GetAll($sql);

				foreach($rs as $rsw)
					$rw[$rsw['var']] = $rsw['val'];
			}	

			xhtml_table($r,$fnames,$fdesc);
		}

	}

	print "<h1>" . T_("Search within this sample") . "</h1>";

	print "<p>" . T_("Use the % character as a wildcard") ."</p>";

	?>
	<form action="" method="get">
	<p>
		<label for="search"><? echo T_("Search for:"); ?></label><input type="text" name="search" id="search"/><br/>
		<input type="hidden" name="sample_import_id" value="<? print($sample_import_id); ?>"/>
		<input type="submit" name="searchsub" value="<? echo T_("Start search"); ?>"/>
	</p>
	</form>
	<?
}
xhtml_foot();


?>
