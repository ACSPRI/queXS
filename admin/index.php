 <?php
 /*

 * Display an index of Admin tools
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
 */

/**
 * Language file
 */
include ("../lang.inc.php");

/**
 * Config file
 */
 include ("../config.inc.php");
 include ("../functions/functions.xhtml.php");
 $username = $_SERVER['PHP_AUTH_USER'];
 ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" style="" class=" " >  
  <head>
    <meta charset="utf-8" >
	<title><?php echo T_("Administrative Tools") ;?> </title> 
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">

<link rel="stylesheet" href="../include/bootstrap-3.3.2/css/bootstrap.min.css" />
<link rel="stylesheet" href="../include/font-awesome-4.3.0/css/font-awesome.css" />
<link rel="stylesheet" href="../css/style.css" />

</head>
  <body>

<div class="page-header-fixed navbar navbar-fixed-top " role="banner">
    <div class="container" style=" width: auto; padding-left: 1px;">
      <div class="navbar-header">
		<a class="pull-left menubutton " href="#"  style="width: 50px; padding-left: 10px;" data-toggle="tooltip" data-placement="right" title="<?php echo T_("Click to Collapse / Expand Sidebar MENU ");?>">
		<i class="fa fa-globe fa-2x fa-spin"></i></a>		
		<a class="navbar-brand" href="index.php"><?php echo COMPANY_NAME ;?> <span class="bold"><?php echo ADMIN_PANEL_NAME ;?></span></a>
	  </div >

	    <ul class="nav navbar-nav pull-right">
          <li class="dropdown pull-right user-data">            
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"  style=" min-width: 160px;">
              <i class="fa fa-user fa fa-fw "></i><?php print T_("Logged as:") . "&ensp;" . $username ;?>           
            </a>
			<!--- User menu // not connected to pages so not working yet //  could be hidden -->
            <ul class="dropdown-menu" role="menu">
              <li><a href="?page=settings.php"><i class="fa fa-cogs fa-fw "></i>&ensp;<?php print T_("Settings"); ?></a></li>
			  <li><a href="../screenloc.php"><i class="fa fa-lock fa-fw "></i>&ensp;<?php print T_("Lock Screen"); ?></a></li>
              <li><a href="../logout.php"><i class="fa fa-sign-out fa-fw "></i>&ensp;<?php print T_("Logout"); ?> </a></li>
            </ul>
          </li>
        </ul>
	  
    </div>
</div>

	<div class="content ">	

	<!-- Sidebar menu -->
	<div class="sidebar" >
	    <ul class="panel-group nav"    id="nav">
		  <li><a class="" href="?page=dashboard.php"><i class="fa fa-tachometer fa-lg"></i><span><?php print T_("Dashboard") ;?></span></a></li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-list-alt fa-lg"></i><span class="arrow"><?php print T_("Questionnairies") ;?></span></a>
              <ul style="">
				<li><a href="?page=<?php echo LIME_URL ;?>admin/admin.php?action=newsurvey"><i class="fa fa-file-text-o lime fa-fw"></i><?php print T_("Create an instrument in Limesurvey") ;?></a></li>
                <li><a href="?page=new.php"><i class="fa fa-plus-circle fa-fw"></i><?php print T_("Create a new questionnaire") ;?></a></li>
                <li><a href="?page=questionnairelist.php"><i class="fa fa-list fa-fw"></i><?php print T_("Questionnaire management") ;?></a></li>
                <li><a href="?page=<?php echo LIME_URL ;?>admin/admin.php"><i class="fa fa-lemon-o lime fa-fw"></i><?php print T_("Administer instruments with Limesurvey") ;?></a></li>
                <li><a href="?page=questionnaireprefill.php"><i class="fa fa-thumb-tack fa-fw"></i><?php print T_("Pre-fill questionnaire") ;?></a></li>
              </ul>
		  </li>
		  <li class="has_sub"><a href="" class=""><i class="fa fa-book fa-lg"></i><span><?php print T_("Samples") ;?></span></a>
              <ul style="">
                <li><a href="?page=import.php"><i class="fa fa-upload fa-fw"></i><?php print T_("Import a sample file") ;?></a></li>
                <li><a href="?page=samplelist.php"><i class="fa fa-list fa-fw"></i><?php print T_("Sample management") ;?></a></li>
                <li><a href="?page=samplesearch.php"><i class="fa fa-search fa-fw"></i><?php print T_("Search the sample") ;?></a></li>
                <li><a href="?page=assignsample.php"><i class="fa fa-link fa-fw"></i><?php print T_("Assign samples to questionnaires") ;?></a></li>
              </ul>
          </li>
		  <li class="has_sub"><a href="" class=""><i class="fa fa-calendar fa-lg"></i><span><?php print T_("Time slots and shifts") ;?></span></a>
              <ul class="" style="">
                <li><a href="?page=assigntimeslots.php"><i class="fa fa-link fa-fw"></i><?php print T_("Assign Time slots") ;?></a></li>
           <!--     <li><a href="?page=questionnaireavailability.php"><i class="fa fa-thumb-tack fa-fw"></i><?php // print T_("Assign Time slots to questionnaires") ;?></a></li>
				<li><a href="?page=questionnairecatimeslots.php"><i class="fa fa-link fa-fw"></i><?php // print T_("Assign call attempt time slots to questionnaire") ; ?></a></li>
				<li><a href="?page=questionnairecatimeslotssample.php"><i class="fa fa-link fa-fw"></i><?php // print T_("Assign call attempt time slots to questionnaire sample") ; ?></a></li> -->
                <li><a href="?page=addshift.php"><i class="fa fa-calendar-o fa-fw"></i><?php print T_("Shift management") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-filter fa-lg"></i><span><?php print T_("Quotas") ;?></span></a>
              <ul style="">
                <li><a href="?page=quota.php"><i class="fa fa-list-ol fa-fw"></i><?php print T_("Quota management") ;?></a></li>
                <li><a href="?page=quotarow.php"><i class="fa fa-list-ul fa-fw "></i><?php print T_("Quota row management") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-users"></i><span><?php print T_("Operators") ;?></span></a> 
              <ul class="" style="">
                <li><a href="?page=operators.php"><i class="fa fa-user-plus fa-fw"></i><?php print T_("Add operators to the system") ;?></a></li>
                <li><a href="?page=operatorlist.php"><i class="fa fa-user fa-fw"></i><?php print T_("Operator management") ;?></a></li>
                <li><a href="?page=extensionstatus.php "><i class="fa fa-phone fa-fw"></i><?php print T_("Extension status") ;?></a></li>
                <li><a href="?page=operatorquestionnaire.php"><i class="fa fa-link fa-fw"></i><?php print T_("Assign operators to questionnaires") ;?></a></li>
                <li><a href="?page=operatorskill.php"><i class="fa fa-user-md fa-fw"></i><?php print T_("Modify operator skills") ;?></a></li>
                <li><a href="?page=operatorperformance.php"><i class="fa fa-signal fa-fw"></i><?php print T_("Operator performance") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-line-chart"></i><span><?php print T_("Results") ;?></span></a>
              <ul class="" style="">
                <li><a href="?page=displayappointments.php"><i class="fa fa-clock-o fa-fw"></i><span><?php print T_("Display all future appointments") ;?></span></a></li>
                <li><a href="?page=samplecallattempts.php"><i class="fa fa-table fa-fw"></i><?php print T_("Sample call attempts report") ;?></a></li>
                <li><a href="?page=callhistory.php" class=""><i class="fa fa-history fa-fw"></i><?php print T_("Call history") ;?></a></li>
                <li><a href="?page=shiftreport.php"><i class="fa fa-th-large fa-fw"></i><?php print T_("Shift reports") ;?></a></li>
                <li><a href="?page=quotareport.php" ><i class="fa fa-filter fa-fw"></i><?php print T_("Quota report") ;?></a></li>
                <li><a href="?page=outcomes.php"><i class="fa fa-bar-chart fa-fw"></i><?php print T_("Questionnaire outcomes") ;?></a></li>
                <li><a href="?page=dataoutput.php"><i class="fa fa-download fa-fw"></i><?php print T_("Data output") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-user-secret fa-fw"></i><span><?php print T_("Clients") ;?></span></a>
              <ul class="" style="">
                <li><a href="?page=clients.php"><i class="fa fa-lg fa-user-plus fa-fw"></i><?php print T_("Add clients to the system") ;?></a></li>
                <li><a href="?page=clientquestionnaire.php"><i class="fa fa-link fa-fw"></i><?php print T_("Assign clients to questionnaires") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-briefcase"></i><span><?php print T_("Supervisor functions") ;?></span></a>
              <ul class="" style="">
                <li><a href="?page=supervisor.php"><i class="fa fa-link fa-fw"></i><?php print T_("Assign outcomes to cases") ;?></a></li>
                <li><a href="?page=casestatus.php"><i class="fa fa-question-circle fa-fw"></i><?php print T_("Case status and assignment") ;?></a></li>
                <li><a href="?page=bulkappointment.php"><i class="fa fa-th-list fa-fw"></i><?php print T_("Bulk appointment generator") ;?></a></li>
              </ul>
          </li>
          <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-gear"></i><span><?php print T_("System settings") ;?></span></a>
              <ul class="" style="">
                <li><a href="?page=timezonetemplate.php"><i class="fa fa-globe fa-fw"></i><?php print T_("Set default timezone list") ;?></a></li>
				<li><a href="?page=availabilitygroup.php"><i class="fa fa-clock-o fa-fw"></i><?php print T_("Manage Time slots") ;?></a></li>
                <li><a href="?page=shifttemplate.php"><i class="fa fa-calendar fa-fw"></i><?php print T_("Set default shift times") ;?></a></li>
                <li><a href="?page=callrestrict.php"><i class="fa fa-clock-o fa-fw"></i><?php print T_("Set call restriction times") ;?></a></li>
                <li><a href="?page=centreinfo.php"><i class="fa fa-university fa-fw"></i><?php print T_("Set centre information") ;?></a></li>
                <li><a href="?page=supervisorchat.php"><i class="fa fa-comments-o fa-fw"></i><?php print T_("Supervisor chat") ;?></a></li>
                <li><a href="?page=systemsort.php"><i class="fa fa-sort fa-fw"></i><?php print T_("System wide case sorting") ;?></a></li>
              </ul>
          </li>

	<?php  
 	if (VOIP_ENABLED == true )
	{ ;	?>
		  <li class="has_sub"><a href="" class=""><i class="fa fa-lg fa-tty"></i><span><?php print T_("VoIP");?><i class="fa fa-toggle-on pull-right" style="font-size:1.5em !important; margin-right:20px;"></i></span></a>
             <ul class="" style="">
               <li><a href="?page=voipmonitor.php"><i class="fa fa-power-off v"></i><?php print T_("Start and monitor VoIP") ;?></a></li>
              <!-- <li><a href="?page=extensionstatus.php"><i class="fa fa-asterisk fa-fw"></i><?php //print T_("Extension status") ;?></a></li> -->
             </ul>
		  </li>
		  
	<?php } else {; ?>
		  <li class=""><a href="" class=""><i class="fa fa-lg fa-tty"></i><span><?php print T_("VoIP") . "&ensp;" . T_("Disabled") ;?><i class="fa fa-toggle-off pull-right" style="font-size:1.5em !important; margin-right:20px;"></i></span></a></li> 
	<?php	}; 	?>
		</ul>
    </div>

	<!-- Main page container -->
	<?php $page = "questionnairelist.php"; if (isset($_GET['page'])) $page = $_GET['page']; ?>
	<div class="mainbar" id=" "><?php xhtml_object($page,' '); ?></div>

		<div class="clearfix"></div>
	</div>

<script src="../js/jquery-2.1.3.min.js"></script>
<script src="../include/bootstrap-3.3.2/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../js/admin.js"></script>	

</body>
</html>
