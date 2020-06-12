<?php 
/**
 *  Allow operator to search for details in samples assigned to them
 *  Then assign as the next available case
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI), 2016
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * Database file
 */
include ("db.inc.php");

/**
 * Authentication
 */
require ("auth-interviewer.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
	$js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

xhtml_head(T_("Search sample"),true,array("css/table.css"),$js);

$operator_id = get_operator_id(); 

if (isset($_GET['callnext']))
{
  $cn = intval($_GET['callnext']);

  $db->StartTrans();
  $sql = "SELECT next_case_id FROM `operator` WHERE operator_id = $operator_id";
  $nc = $db->GetOne($sql);
  if (!empty($nc))
    print "<p>" . T_("Already calling case") . " $nc " . T_("next") . "</p>";
  else
  {
    $sql = "UPDATE `operator` SET next_case_id = $cn WHERE operator_id = $operator_id";
    $db->Execute($sql);
    print "<p>" . T_("Will call case") . " $cn " . T_("next") . "</p>";
  }
  $db->CompleteTrans();
}

$rs = "";

//search
if (isset($_POST['search'])) {
	//display sample details
	//limit to those allowed by admin
	//
	$search = $db->qstr("%" . $_POST['search'] . "%");

	$sql = "SELECT c.case_id,CASE WHEN op.next_case_id IS NULL THEN CONCAT('<a href=\"?callnext=',c.case_id,'\">".T_("Call next")."</a>') ELSE CONCAT('".T_("Calling case")." ', op.next_case_id, ' ".T_("next")."') END as callnext,
		sv.val,sivr.var
		FROM `sample_var` as sv, `sample_import_var_restrict` as sivr, `case` as c, `operator_questionnaire` as oq, `questionnaire_sample` as qs, `sample` as s, operator as op
		WHERE oq.operator_id = '$operator_id'
		AND op.operator_id = oq.operator_id
		AND oq.questionnaire_id = qs.questionnaire_id
		AND s.import_id = qs.sample_import_id
		AND c.sample_id = s.sample_id
		AND c.questionnaire_id = qs.questionnaire_id
		AND sv.sample_id = s.sample_id
		AND sivr.var_id = sv.var_id
		AND (sivr.restrict IS NULL OR sivr.restrict = 0)
		AND sv.val like $search";

	$rs = $db->GetAll($sql);

	if (!empty($rs))
	{
		xhtml_table($rs,array("case_id","var","val","callnext"),array(T_("Case id"),T_("Var"),T_("Value"),T_("Call next")));
	} else {
		print "<p>" . T_("No results") . "</p>";
	}
}

//display search form
	  print "<form action='?' method='post'>";
	  print "<label for='search'>" . T_("Search sample") . ":</label>";
	  print "<input type='text' id='search' name='search'/>";
  	  print "<p><input type='submit' value='" .T_("Search sample") . "'/></p></form>";


		

xhtml_foot();

?>
