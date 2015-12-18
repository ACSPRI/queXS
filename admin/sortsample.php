<?php 
/**
 * Sort samples globally
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
 * Authentication
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
"../js/window.js"
				);
$js_foot = array(
"../js/custom.js"
				);
global $db;

if (isset($_GET['sort_order']) && isset($_GET['sort']))   
{
	$sort_order = $_GET['sort_order'];
	if ($_GET['sort'] == "up") $so = $sort_order -1;
	if ($_GET['sort'] == "down") $so = $sort_order +1;
	
	$sql = "UPDATE questionnaire_sample 
		SET sort_order = IF(sort_order = $sort_order, $so, $sort_order)
		WHERE sort_order IN( $sort_order, $so)";

	$db->Execute($sql);
	unset($_GET['sort']); unset($_GET['sort_order']);
}

/* auto-set continiuos sort_order values for existing questionnaire_samples if not set before or first-time run */
if ($db->GetOne("SELECT COUNT(sort_order) - COUNT(DISTINCT sort_order ) FROM questionnaire_sample") >0){
		$db->Execute("SELECT @i := 0");
		$db->Execute("UPDATE `questionnaire_sample` SET sort_order = @i:=@i+1 WHERE 1=1 ORDER BY sort_order ASC");
}

$subtitle = T_("List and sort samples");
xhtml_head(T_("Sort questionnaire samples"),true,$css,$js_head,false,false,false,$subtitle);//array("../css/table.css"),array("../js/window.js")

print "<a href='' onclick='history.back();return false;' class='btn btn-default pull-left'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";

print "<h2 class='col-lg-offset-2'><i class='fa fa-sort-numeric-asc text-primary'></i>&emsp;". T_("Sort order for questionnaire samples") ."</h2>
		<div class='panel-body'>";

$sql = "SELECT q.sort_order as sort_order, si.description as description,si.sample_import_id, q.questionnaire_id,
		CONCAT('<a href=\"assignsample.php?questionnaire_id=', q.questionnaire_id ,'\" data-toggle=\'tooltip\' title=\'". TQ_("Samples selected for this questionnaire") ." ', q.questionnaire_id ,'\' class=\'  \'><h4>', qu.description ,' </h4></a>') as qdesc, 
		CASE WHEN q.call_max = 0 THEN '". TQ_("Unlimited") ."' ELSE q.call_max END as call_max,
		CASE WHEN q.call_attempt_max = 0 THEN '". TQ_("Unlimited") . "' ELSE q.call_attempt_max END AS call_attempt_max,
		CASE WHEN q.random_select = 0 THEN '". TQ_("Sequential") ."' ELSE '". TQ_("Random") . "' END as random_select,
		CASE WHEN q.answering_machine_messages = 0 THEN '". TQ_("Never") . "' ELSE q.answering_machine_messages END as answering_machine_messages,
		CONCAT('<a href=\"assignsample.php?edit=edit&amp;questionnaire_id=', q.questionnaire_id ,'&amp;rsid=', si.sample_import_id ,'\" data-toggle=\'tooltip\' title=\'". TQ_("Edit") ."\' class=\'btn center-block\'><i class=\'fa fa-pencil-square-o fa-lg\'></i></a>') as edit
		FROM questionnaire_sample as q, sample_import as si, questionnaire as qu
		WHERE q.sample_import_id = si.sample_import_id
		AND q.questionnaire_id = qu.questionnaire_id
		AND qu.enabled = 1
		ORDER BY q.sort_order ASC";
$qs = $db->GetAll($sql);

if (!empty($qs))
{
    $co = count($qs);
    if ($co > 1)
    {
      for($i = 0; $i < $co; $i++)
      {
        $down = "<a href='?sort_order={$qs[$i]['sort_order']}&amp;sort=down' data-toggle=\"tooltip\" title=\"". T_("Pull step Down") ."\"><i class=\"fa fa-angle-down fa-2x\"></i></a>";
        $up = "<a href='?sort_order={$qs[$i]['sort_order']}&amp;sort=up' data-toggle=\"tooltip\" title=\"". T_("Push step Up") ."\"><i class=\"fa fa-angle-up fa-2x\"></i></a>";
        if ($i == 0) //down only
        {
          $qs[$i]['sort_order'] = "<div>&emsp;&emsp;   <span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>&emsp;" . $down . "</div>";
        }
        else if ($i == ($co - 1)) //up only
        {
          $qs[$i]['sort_order'] = "<div style=\"min-width:5em;\"> " .$up . "&emsp;<span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>"; 
        }
        else
        {
          $qs[$i]['sort_order'] = "<div> " . $up . "&emsp;<span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>&emsp;" . $down . "</div>";
        }
      }
    }
    else
      $qs[0]['sort_order'] = "&emsp;<i class=\"fa fa-minus fa-lg\"></i>&emsp;";

    xhtml_table($qs,array("sort_order","qdesc","description","call_max","call_attempt_max","answering_machine_messages","random_select","edit"),array(T_("Sort order"), T_("Questionnaire"),T_("Sample"), T_("Max calls"), T_("Max call attempts"), T_("Answering machine messages"), T_("Selection type"), T_("Edit")));
}
	else
		print "<div class='alert text-danger'><h4>". T_("No samples assigned to questionnaires") ."</h4></div>";

	print"</div>";


xhtml_foot($js_foot);

?>
