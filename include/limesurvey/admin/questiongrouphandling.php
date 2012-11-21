<?php
/*
 * LimeSurvey
 * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * $Id: questiongrouphandling.php 11664 2011-12-16 05:19:42Z tmswhite $
 */


//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

if ($action == "addgroup")
{
    $grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $grplangs[] = $baselang;
    $grplangs = array_reverse($grplangs);

    $newgroupoutput = PrepareEditorScript();
    $newgroupoutput .= "<div class='header ui-widget-header'>".$clang->gT("Add question group")."</div>\n";
     $newgroupoutput .= "<div id='tabs'>\n<ul>\n";
	 foreach ($grplangs as $grouplang)
    {
        $newgroupoutput .= '<li><a href="#'.$grouplang.'">'.GetLanguageNameFromCode($grouplang,false);
        if ($grouplang==$baselang) {$newgroupoutput .= '('.$clang->gT("Base language").')';}
        $newgroupoutput .= "</a></li>\n";
		}
		if (bHasSurveyPermission($surveyid,'surveycontent','import'))
    {
        $newgroupoutput .= '<li><a href="#import">'.$clang->gT("Import question group")."</a></li>\n";

	}
		$newgroupoutput .= "</ul>";

    //    $newgroupoutput .="<table width='100%' border='0'  class='tab-page'>\n\t<tr><td>\n"
    $newgroupoutput .="\n";
    $newgroupoutput .= "<form action='$scriptname' class='form30' id='newquestiongroup' name='newquestiongroup' method='post' onsubmit=\"if (1==0 ";

    foreach ($grplangs as $grouplang)
    {
        $newgroupoutput .= "|| document.getElementById('group_name_$grouplang').value.length==0 ";
    }
    $newgroupoutput .=" ) {alert ('".$clang->gT("Error: You have to enter a group title for each language.",'js')."'); return false;}\" >";

    foreach ($grplangs as $grouplang)
    {
        $newgroupoutput .= '<div id="'.$grouplang.'">';
        $newgroupoutput .= "<ul>"
        . "<li>"
        . "<label for='group_name_$grouplang'>".$clang->gT("Title").":</label>\n"
        . "<input type='text' size='80' maxlength='100' name='group_name_$grouplang' id='group_name_$grouplang' /><font color='red' face='verdana' size='1'> ".$clang->gT("Required")."</font></li>\n"
        . "\t<li><label for='description_$grouplang'>".$clang->gT("Description:")."</label>\n"
        . "<textarea cols='80' rows='8' id='description_$grouplang' name='description_$grouplang'></textarea>"
        . getEditor("group-desc","description_".$grouplang, "[".$clang->gT("Description:", "js")."](".$grouplang.")",$surveyid,'','',$action)
        . "</li>\n"
        // Group-Level Relevance
        . "<li><label for='grelevance'>".$clang->gT("Relevance equation:")."</label>"
        . "<textarea cols='50' rows='1' id='grelevance' name='grelevance'></textarea>"
        . "</li>"
        . "</ul>"
        . "\t<p><input type='submit' value='".$clang->gT("Save question group")."' />\n"
        . "</div>\n";
    }

    $newgroupoutput.= "<input type='hidden' name='action' value='insertquestiongroup' />\n"
    . "<input type='hidden' name='sid' value='$surveyid' />\n"
    . "</form>\n";


    // Import TAB
    if (bHasSurveyPermission($surveyid,'surveycontent','import'))
    {
        $newgroupoutput .= '<div id="import">'."\n"
        . "<form enctype='multipart/form-data' class='form30' id='importgroup' name='importgroup' action='$scriptname' method='post' onsubmit='return validatefilename(this,\"".$clang->gT('Please select a file to import!','js')."\");'>\n"
        . "<ul>\n"
        . "<li>\n"
        . "<label for='the_file'>".$clang->gT("Select question group file (*.lsg/*.csv):")."</label>\n"
        . "<input id='the_file' name=\"the_file\" type=\"file\" size=\"35\" /></li>\n"
        . "<li><label for='translinksfields'>".$clang->gT("Convert resource links?")."</label>\n"
        . "<input id='translinksfields' name=\"translinksfields\" type=\"checkbox\" checked=\"checked\"/></li></ul>\n"
        . "\t<p><input type='submit' value='".$clang->gT("Import question group")."' />\n"
        . "\t<input type='hidden' name='action' value='importgroup' />\n"
        . "\t<input type='hidden' name='sid' value='$surveyid' />\n"
        . "\t</form>\n";
        // End Import TABS
        $newgroupoutput.= "</div>";
    }



    // End of TABS
     $newgroupoutput.= "</div>";


}


if ($action == "editgroup")
{
    $grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);

    $grplangs[] = $baselang;
    $grplangs = array_flip($grplangs);

    $egquery = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND gid=$gid";
    $egresult = db_execute_assoc($egquery);
    while ($esrow = $egresult->FetchRow())
    {
        if(!array_key_exists($esrow['language'], $grplangs)) // Language Exists, BUT ITS NOT ON THE SURVEY ANYMORE.
        {
            $egquery = "DELETE FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND gid='{$gid}' AND language='".$esrow['language']."'";
            $egresultD = $connect->Execute($egquery);
        } else {
            $grplangs[$esrow['language']] = 99;
        }
        if ($esrow['language'] == $baselang) $basesettings = array('group_name' => $esrow['group_name'],'description' => $esrow['description'],'group_order' => $esrow['group_order'], 'grelevance' => $esrow['grelevance']);

    }

    while (list($key,$value) = each($grplangs))
    {
        if ($value != 99)
        {
            $egquery = "INSERT INTO ".db_table_name('groups')." (gid, sid, group_name, description,group_order, grelevance, language) VALUES ('{$gid}', '{$surveyid}', '{$basesettings['group_name']}', '{$basesettings['description']}','{$basesettings['group_order']}', '{$basesettings['grelevance']}', '{$key}')";
            $egresult = $connect->Execute($egquery);
        }
    }

    $egquery = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND gid=$gid AND language='$baselang'";
    $egresult = db_execute_assoc($egquery);
    $editgroup = PrepareEditorScript();
    $esrow = $egresult->FetchRow();
    $tab_title[0] = getLanguageNameFromCode($esrow['language'],false). '('.$clang->gT("Base language").')';
    $esrow = array_map('htmlspecialchars', $esrow);
    $tab_content[0] = "<div class='settingrow'><span class='settingcaption'><label for='group_name_{$esrow['language']}'>".$clang->gT("Title").":</label></span>\n"
        . "<span class='settingentry'><input type='text' maxlength='100' size='80' name='group_name_{$esrow['language']}' id='group_name_{$esrow['language']}' value=\"{$esrow['group_name']}\" />\n"
        . "\t</span></div>\n"
        . "<div class='settingrow'><span class='settingcaption'><label for='description_{$esrow['language']}'>".$clang->gT("Description:")."</label>\n"
        . "</span><span class='settingentry'><textarea cols='70' rows='8' id='description_{$esrow['language']}' name='description_{$esrow['language']}'>{$esrow['description']}</textarea>\n"
        . getEditor("group-desc","description_".$esrow['language'], "[".$clang->gT("Description:", "js")."](".$esrow['language'].")",$surveyid,$gid,'',$action)
        . "</span></div>"
        . "<div class='settingrow'><span class='settingcaption'><label for='grelevance'>".$clang->gT("Relevance equation:")."</label></span>\n"
        . "<span class='settingentry'><textarea cols='50' rows='1' id='grelevance' name='grelevance'>".$esrow['grelevance']."</textarea></span>"
        . "</span></div>"
        . "\n<div style='clear:both'></div>";
    $egquery = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND gid=$gid AND language!='$baselang'";
    $egresult = db_execute_assoc($egquery);
    $i = 1;
    while ($esrow = $egresult->FetchRow())
    {
        $tab_title[$i] = getLanguageNameFromCode($esrow['language'],false);
        $esrow = array_map('htmlspecialchars', $esrow);
        $tab_content[$i] = "<div class='settingrow'><span class='settingcaption'><label for='group_name_{$esrow['language']}'>".$clang->gT("Title").":</label></span>\n"
            . "<span class='settingentry'><input type='text' maxlength='100' size='80' name='group_name_{$esrow['language']}' id='group_name_{$esrow['language']}' value=\"{$esrow['group_name']}\" />\n"
            . "\t</span></div>\n"
            . "<div class='settingrow'><span class='settingcaption'><label for='description_{$esrow['language']}'>".$clang->gT("Description:")."</label>\n"
            . "</span><span class='settingentry'><textarea cols='70' rows='8' id='description_{$esrow['language']}' name='description_{$esrow['language']}'>{$esrow['description']}</textarea>\n"
            . getEditor("group-desc","description_".$esrow['language'], "[".$clang->gT("Description:", "js")."](".$esrow['language'].")",$surveyid,$gid,'',$action)
            . "\t</span></div><div style='clear:both'></div>";
        $i++;
    }

    $editgroup .= "<div class='header ui-widget-header'>".$clang->gT("Edit Group")."</div>\n"
    . "<form name='frmeditgroup' id='frmeditgroup' action='$scriptname' class='form30' method='post'>\n<div id='tabs'><ul>\n";


    foreach ($tab_title as $i=>$eachtitle){
        $editgroup .= "\t<li style='clear:none'><a href='#editgrp$i'>$eachtitle</a></li>\n";

    }
    $editgroup.="</ul>\n";

    foreach ($tab_content as $i=>$eachcontent){
        $editgroup .= "\n<div id='editgrp$i'>$eachcontent</div>";
    }

    $editgroup .= "</div>\n\t<p><input type='submit' class='standardbtn' value='".$clang->gT("Update Group")."' />\n"
    . "\t<input type='hidden' name='action' value='updategroup' />\n"
    . "\t<input type='hidden' name='sid' value=\"{$surveyid}\" />\n"
    . "\t<input type='hidden' name='gid' value='{$gid}' />\n"
    . "\t<input type='hidden' name='language' value=\"{$esrow['language']}\" />\n"
    . "\t</p>\n"
    . "</form>\n";

}


if ($action == "ordergroups")
{
    if(bHasSurveyPermission($surveyid,'surveycontent','update'))
    {
        // Check if one of the up/down buttons have been clicked
        if (isset($_POST['groupordermethod']) && isset($_POST['sortorder']))
        {
            $postsortorder=sanitize_int($_POST['sortorder']);
            switch($_POST['groupordermethod'])
            {
                // Pressing the Up button
                case 'up':
                    $newsortorder=$postsortorder-1;
                    $oldsortorder=$postsortorder;
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=-1 WHERE sid=$surveyid AND group_order=$newsortorder";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg()); //Checked
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=$newsortorder WHERE sid=$surveyid AND group_order=$oldsortorder";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg()); //Checked
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order='$oldsortorder' WHERE sid=$surveyid AND group_order=-1";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg()); //Checked
                    break;

                    // Pressing the Down button
                case 'down':
                    $newsortorder=$postsortorder+1;
                    $oldsortorder=$postsortorder;
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=-1 WHERE sid=$surveyid AND group_order=$newsortorder";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());//Checked
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order='$newsortorder' WHERE sid=$surveyid AND group_order=$oldsortorder";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());//Checked
                    $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=$oldsortorder WHERE sid=$surveyid AND group_order=-1";
                    $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());//Checked
                    break;
            }
            LimeExpressionManager::SetDirtyFlag(); // so refreshes syntax highlighting
        }
        // Move the question to specific position
        if ((!empty($_POST['groupmovefrom']) || (isset($_POST['groupmovefrom']) && $_POST['groupmovefrom'] == '0')) && (!empty($_POST['groupmoveto']) || (isset($_POST['groupmoveto']) && $_POST['groupmoveto'] == '0')))
        {
            $newpos=(int)$_POST['groupmoveto'];
            $oldpos=(int)$_POST['groupmovefrom'];
            if($newpos > $oldpos)
            {
                //Move the group we're changing out of the way
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=-1 WHERE sid=$surveyid AND group_order=$oldpos";
                $cdresult=$connect->Execute($cdquery) or safe_die($cdquery."<br />".$connect->ErrorMsg());
                //Move all question_orders that are less than the newpos down one
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=group_order-1 WHERE sid=$surveyid AND group_order > $oldpos and group_order<=$newpos";
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());
                //Renumber the question we're changing
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=$newpos WHERE sid=$surveyid AND group_order=-1";
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());
            }
            if(($newpos+1) < $oldpos)
            {
                //echo "Newpos $newpos, Oldpos $oldpos";
                //Move the question we're changing out of the way
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=-1 WHERE sid=$surveyid AND group_order=$oldpos";
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());
                //Move all question_orders that are later than the newpos up one
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=group_order+1 WHERE sid=$surveyid AND group_order > $newpos AND group_order <= $oldpos";
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());
                //Renumber the question we're changing
                $cdquery = "UPDATE ".db_table_name('groups')." SET group_order=".($newpos+1)." WHERE sid=$surveyid AND group_order=-1";
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());
            }
            LimeExpressionManager::SetDirtyFlag(); // so refreshes syntax highlighting
        }

        $ordergroups = "<div class='header ui-widget-header'>".$clang->gT("Change Group Order")."</div><br />\n";

        $ordergroups .= "<form method='post' action=''><ul class='movableList'>";
        //Get the groups from this survey
        $s_lang = GetBaseLanguageFromSurveyID($surveyid);
        $ogquery = "SELECT * FROM {$dbprefix}groups WHERE sid='{$surveyid}' AND language='{$s_lang}' order by group_order,group_name" ;
        $ogresult = db_execute_assoc($ogquery) or safe_die($connect->ErrorMsg());//Checked

        $ogarray = $ogresult->GetArray();
        //FIX BROKEN ORDER
        //Check if all group_order numbers are consecutive
        $consecutive=true;
        $lastnumber=-1;
        foreach($ogarray as $group)
        {
            if(($group['group_order']-1) != $lastnumber)
            {
                $consecutive=false;
            }
            $lastnumber=$group['group_order'];
        }
        //Fix bad ordering
        if((isset($ogarray[0]['group_order']) && $ogarray[0]['group_order'] > 0) || !$consecutive)
        {
            $i=0;
            foreach($ogarray as $group)
            {
                $fixorderq = "UPDATE ".db_table_name('groups')." SET group_order=$i WHERE sid=$surveyid AND group_order = ".$group['group_order'];
                $foresult = db_execute_assoc($fixorderq) or safe_die($connect->ErrorMsg());
                $ogarray[$i]['group_order']=$i;
                $i++;
            }
        }
        //END FIX BROKEN ORDER
        $miniogarray=$ogarray;
        $groupcount = count($ogarray);
        for($i=0; $i < $groupcount ; $i++)
        {
            $downdisabled = "";
            $updisabled = "";

            $ordergroups.="<li class='movableNode' id='gid".$ogarray[$i]['gid']."'>\n" ;

            // DROP DOWN LIST //
            //Move to location
            //$ordergroups.="<li class='movableNode'>\n" ;
            $ordergroups.="\t<select style='float:right; margin-left: 5px; width:20em;";
            $ordergroups.="' name='groupmovetomethod$i' onchange=\"this.form.groupmovefrom.value='".$ogarray[$i]['group_order']."';this.form.groupmoveto.value=this.value;submit()\">\n";
            $ordergroups.="<option value=''>".$clang->gT("Place after..")."</option>\n";
            //Display the "position at beginning" item
            if(empty($groupdepsarray) || (!is_null($groupdepsarray)  && $i != 0 &&
            !array_key_exists($ogarray[$i]['gid'], $groupdepsarray)))
            {
                $ordergroups.="<option value='-1'>".$clang->gT("At beginning")."</option>\n";
            }
            //Find out if there are any dependencies
            $max_start_order=0;
            //Find out if any groups use this as a dependency
            $max_end_order=$groupcount+1; //By default, stop the list at the last group

            $minipos=$miniogarray[0]['group_order']; //Start at the very first group_order
            foreach($miniogarray as $mo)
            {
                if($minipos >= $max_start_order && $minipos < $max_end_order && $i!=$mo['group_order'] && $i-1!=$mo['group_order'])
                {
                    $ordergroups.="<option value='".$mo['group_order']."'>".$mo['group_name']."</option>\n";
                }
                $minipos++;
            }
            $ordergroups.="</select>\n";

            // BUTTONS //
            $ordergroups.= "<input style='float:right;";

            if ($i == 0){$ordergroups.="visibility:hidden;";}
            $ordergroups.="' type='image' src='$imageurl/up.png' name='btnup_$i' onclick=\"$('#sortorder').val('{$ogarray[$i]['group_order']}');$('#groupordermethod').val('up')\" ".$updisabled."/>\n";

            if ($i < $groupcount-1)
            {
                // Fill the hidden field 'sortorder' so we know what field is moved down
                $ordergroups.= "<input type='image' src='$imageurl/down.png' style='float:right;' name='btndown_$i' onclick=\"$('#sortorder').val('{$ogarray[$i]['group_order']}');$('#groupordermethod').val('down')\" ".$downdisabled."/>\n";
            }
            $ordergroups.=$ogarray[$i]['group_name']."</li>\n" ;

        }

        $ordergroups.="</ul>\n"
        . "<input type='hidden' name='groupmovefrom' />\n"
        . "<input type='hidden' id='groupordermethod' name='groupordermethod' />\n"
        . "<input type='hidden' name='groupmoveto' />\n"
        . "<input type='hidden' id='sortorder' name='sortorder' />"
        . "<input type='hidden' name='action' value='ordergroups' />"
        . "</form>" ;
        $ordergroups .="<br />" ;
    }
    else
    {
        include("access_denied.php");
    }
}


?>
