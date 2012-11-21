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
    * $Id: quota.php 12303 2012-02-02 10:49:40Z c_schmitz $
    */

    include_once("login_check.php");  //Login Check dies also if the script is started directly

    function getQuotaAnswers($qid,$surveyid,$quota_id)
    {
        global $clang;
        $baselang = GetBaseLanguageFromSurveyID($surveyid);
        $query = "SELECT type, title FROM ".db_table_name('questions')."q JOIN ".db_table_name('groups')."g on g.gid=q.gid WHERE qid='{$qid}' AND q.language='{$baselang}' AND g.language='{$baselang}' order by group_order, question_order";
        $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
        $qtype = $result->FetchRow();

        if ($qtype['type'] == 'G')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";

            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array('M' => array('Title' => $qtype['title'], 'Display' => $clang->gT("Male"), 'code' => 'M'),
            'F' => array('Title' => $qtype['title'],'Display' => $clang->gT("Female"), 'code' => 'F'));

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if ($qtype['type'] == 'M')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $query = "SELECT title,question FROM ".db_table_name('questions')." WHERE parent_qid='{$qid}'";
            $ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array();

            while ($dbanslist = $ansresult->FetchRow())
            {
                $tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['question'],0,40), 'code' => $dbanslist['title']);
                $answerlist[$dbanslist['title']]	= $tmparrayans;
            }

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if ($qtype['type'] == 'L' || $qtype['type'] == 'O' || $qtype['type'] == '!')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}' and language='{$baselang}'";
            $ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array();

            while ($dbanslist = $ansresult->FetchRow())
            {
                $answerlist[$dbanslist['code']] = array('Title'=>$qtype['title'],
                'Display'=>substr($dbanslist['answer'],0,40),
                'code'=>$dbanslist['code']);
            }

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }

        }

        if ($qtype['type'] == 'A')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $query = "SELECT title,question FROM ".db_table_name('questions')." WHERE parent_qid='{$qid}'";
            $ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array();

            while ($dbanslist = $ansresult->FetchRow())
            {
                for ($x=1; $x<6; $x++)
                {
                    $tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['question'],0,40).' ['.$x.']', 'code' => $dbanslist['title']);
                    $answerlist[$dbanslist['title']."-".$x]	= $tmparrayans;
                }
            }

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if ($qtype['type'] == 'B')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}' and language='{$baselang}'";
            $ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array();

            while ($dbanslist = $ansresult->FetchRow())
            {
                for ($x=1; $x<11; $x++)
                {
                    $tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['answer'],0,40).' ['.$x.']', 'code' => $dbanslist['code']);
                    $answerlist[$dbanslist['code']."-".$x]	= $tmparrayans;
                }
            }

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if ($qtype['type'] == 'Y')
        {
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";

            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            $answerlist = array('Y' => array('Title' => $qtype['title'], 'Display' => $clang->gT("Yes"), 'code' => 'Y'),
            'N' => array('Title' => $qtype['title'],'Display' => $clang->gT("No"), 'code' => 'N'));

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if ($qtype['type'] == 'I')
        {

            $slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
            array_unshift($slangs,$baselang);

            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            while(list($key,$value) = each($slangs))
            {
                $tmparrayans = array('Title' => $qtype['title'], 'Display' => getLanguageNameFromCode($value,false), $value);
                $answerlist[$value]	= $tmparrayans;
            }

            if ($result->RecordCount() > 0)
            {
                while ($quotalist = $result->FetchRow())
                {
                    $answerlist[$quotalist['code']]['rowexists'] = '1';
                }

            }
        }

        if (!isset($answerlist))
        {
            return array();
        }
        else
        {
            return $answerlist;
        }
    }

    $js_admin_includes[]='../scripts/jquery/jquery.tablesorter.min.js';
    $js_admin_includes[]='scripts/quotas.js';

    if(bHasSurveyPermission($surveyid, 'quotas','read'))
    {
        if (isset($_POST['quotamax'])) $_POST['quotamax']=sanitize_int($_POST['quotamax']);
        if (!isset($action)) $action=returnglobal('action');
        if (!isset($subaction)) $subaction=returnglobal('subaction');
        if (!isset($quotasoutput)) $quotasoutput = "";
        //if (!isset($_POST['autoload_url']) || empty($_POST['autoload_url'])) {$_POST['autoload_url']=0;} //queXS Removal
        if($subaction == "insertquota" && bHasSurveyPermission($surveyid, 'quotas','create'))
        {
            if(!isset($_POST['quota_limit']) || $_POST['quota_limit'] < 0 || empty($_POST['quota_limit']) || !is_numeric($_POST['quota_limit']))
            {
                $_POST['quota_limit'] = 0;

            }

            array_walk( $_POST, 'db_quote', true);

            $query = "INSERT INTO ".db_table_name('quota')." (sid,name,qlimit,action,autoload_url)
            VALUES ('$surveyid','{$_POST['quota_name']}','{$_POST['quota_limit']}','1', '1')";
            $connect->Execute($query) or safe_die("Error inserting limit".$connect->ErrorMsg());
            $quotaid=$connect->Insert_Id(db_table_name_nq('quota'),"id");

            //Get the languages used in this survey
            $langs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_push($langs, $baselang);
            //Iterate through each language, and make sure there is a quota message for it
            $errorstring = '';
            foreach ($langs as $lang)
            {
                if (!$_POST['quotals_message_'.$lang]) { $errorstring.= GetLanguageNameFromCode($lang,false)."\\n";}
            }
            if ($errorstring!='')
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Quota could not be added.\\n\\nIt is missing a quota message for the following languages","js").":\\n".$errorstring."\")\n //-->\n</script>\n";
            }
            else
            //All the required quota messages exist, now we can insert this info into the database
            {
                require_once("../classes/inputfilter/class.inputfilter_clean.php");
                $myFilter = new InputFilter('','',1,1,1);

                foreach ($langs as $lang) //Iterate through each language
                {
                    //Clean XSS
                    if ($filterxsshtml)
                    {
                        $_POST['quotals_message_'.$lang]=$myFilter->process($_POST['quotals_message_'.$lang]);
                    }
                    else
                    {
                        $_POST['quotals_message_'.$lang] = html_entity_decode($_POST['quotals_message_'.$lang], ENT_QUOTES, "UTF-8");
                    }

                    // Fix bug with FCKEditor saving strange BR types
                    $_POST['quotals_message_'.$lang]=fix_FCKeditor_text($_POST['quotals_message_'.$lang]);

	            include_once(dirname(__FILE__) . '/../quexs.php'); //queXS Addition

                    //Now save the language to the database:
                    $query = "INSERT INTO ".db_table_name('quota_languagesettings')." (quotals_quota_id, quotals_language, quotals_name, quotals_message, quotals_url, quotals_urldescrip)
                    VALUES ('$quotaid', '$lang', '".db_quote($_POST['quota_name'],true)."', '".db_quote($_POST['quotals_message_'.$lang],true)."', '".QUEXS_URL."rs_quota_end.php"."', '".QUEXS_URL."rs_quota_end.php"."')";
                    $connect->Execute($query) or safe_die($connect->ErrorMsg());
                }
            } //End insert language based components
            $viewquota = "1";

        } //End foreach $lang

        if($subaction == "modifyquota" && bHasSurveyPermission($surveyid, 'quotas','update'))
        {
            $query = "UPDATE ".db_table_name('quota')."
            SET name=".db_quoteall($_POST['quota_name'],true).",
            qlimit=".db_quoteall($_POST['quota_limit'],true)."
            WHERE id=".db_quoteall($_POST['quota_id'],true);
            $connect->Execute($query) or safe_die("Error modifying quota".$connect->ErrorMsg());

            //Get the languages used in this survey
            $langs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_push($langs, $baselang);
            //Iterate through each language, and make sure there is a quota message for it
            $errorstring = '';
            foreach ($langs as $lang)
            {
                if (!$_POST['quotals_message_'.$lang]) { $errorstring.= GetLanguageNameFromCode($lang,false)."\\n";}
            }
            if ($errorstring!='')
            {
                $quotasoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Quota could not be added.\\n\\nIt is missing a quota message for the following languages","js").":\\n".$errorstring."\")\n //-->\n</script>\n";
            }
            else
            //All the required quota messages exist, now we can insert this info into the database
            {
                require_once("../classes/inputfilter/class.inputfilter_clean.php");
                $myFilter = new InputFilter('','',1,1,1);

                foreach ($langs as $lang) //Iterate through each language
                {
                    //Clean XSS
                    if ($filterxsshtml)
                    {
                        $_POST['quotals_message_'.$lang]=$myFilter->process($_POST['quotals_message_'.$lang]);
                    }
                    else
                    {
                        $_POST['quotals_message_'.$lang] = html_entity_decode($_POST['quotals_message_'.$lang], ENT_QUOTES, "UTF-8");
                    }

                    // Fix bug with FCKEditor saving strange BR types
                    $_POST['quotals_message_'.$lang]=fix_FCKeditor_text($_POST['quotals_message_'.$lang]);

                    //Check to see if a matching language exists, and if not, INSERT one (no update possible)
                    $query = "SELECT * FROM ".db_table_name('quota_languagesettings')."
                    WHERE quotals_quota_id = ".db_quote($_POST['quota_id'], true)."
                    AND quotals_language = '$lang'";
                    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
                    if ($result->RecordCount() > 0) {
                        //Now save the language to the database:
                        $query = "UPDATE ".db_table_name('quota_languagesettings')."
                        SET quotals_name='".db_quote($_POST['quota_name'],true)."',
                        quotals_message='".db_quote($_POST['quotals_message_'.$lang],true)."'
                        WHERE quotals_quota_id =".db_quote($_POST['quota_id'],true)."
                        AND quotals_language = '$lang'";
                        $connect->Execute($query) or safe_die($connect->ErrorMsg());
                    } else {
                        /* If there is no matching record for this language, create one */
                        $query = "INSERT INTO ".db_table_name('quota_languagesettings')."
                        (quotals_quota_id,quotals_language,quotals_name,quotals_message,quotals_url,quotals_urldescrip)
                        VALUES ('".db_quote($_POST['quota_id'])."', '$lang', '".db_quote($_POST['quota_name'],true)."',
                        '".db_quote($_POST['quotals_message_'.$lang],true)."', '".QUEXS_URL."rs_quota_end.php"."',
                        '".QUEXS_URL."rs_quota_end.php"."')";
                        $connect->Execute($query) or safe_die($connect->ErrorMsg());
                    }
                }
            } //End insert language based components


            $viewquota = "1";
        }

        if($subaction == "insertquotaanswer" && bHasSurveyPermission($surveyid, 'quotas','create'))
        {
            array_walk( $_POST, 'db_quote', true);
            $query = "INSERT INTO ".db_table_name('quota_members')." (sid,qid,quota_id,code) VALUES ('$surveyid','{$_POST['quota_qid']}','{$_POST['quota_id']}','{$_POST['quota_anscode']}')";
            $connect->Execute($query) or safe_die($connect->ErrorMsg());
            if(isset($_POST['createanother']) && $_POST['createanother'] == "on") {
                $_POST['action']="quotas";
                $_POST['subaction']="new_answer";
                $subaction="new_answer";
            } else {
                $viewquota = "1";
            }
        }

        if($subaction == "quota_delans" && bHasSurveyPermission($surveyid, 'quotas','delete'))
        {
            array_walk( $_POST, 'db_quote', true);
            $query = "DELETE FROM ".db_table_name('quota_members')."
            WHERE id = '{$_POST['quota_member_id']}'
            AND qid='{$_POST['quota_qid']}' and code='{$_POST['quota_anscode']}'";
            $connect->Execute($query) or safe_die($connect->ErrorMsg());
            $viewquota = "1";

        }

        if($subaction == "quota_delquota" && bHasSurveyPermission($surveyid, 'quotas','delete'))
        {
            array_walk( $_POST, 'db_quote', true);
            $query = "DELETE FROM ".db_table_name('quota')." WHERE id='{$_POST['quota_id']}'";
            $connect->Execute($query) or safe_die($connect->ErrorMsg());

            $query = "DELETE FROM ".db_table_name('quota_languagesettings')." WHERE quotals_quota_id='{$_POST['quota_id']}'";
            $connect->Execute($query) or safe_die($connect->ErrorMsg());

            $query = "DELETE FROM ".db_table_name('quota_members')." WHERE quota_id='{$_POST['quota_id']}'";
            $connect->Execute($query) or safe_die($connect->ErrorMsg());
            $viewquota = "1";
        }

        if ($subaction == "quota_editquota" && bHasSurveyPermission($surveyid, 'quotas','update'))
        {
	    if (isset($_GET['quota_id'])) $_POST['quota_id'] = $_GET['quota_id']; //queXS Addition
            array_walk( $_POST, 'db_quote', true);
            $query = "SELECT * FROM ".db_table_name('quota')."
            WHERE id='{$_POST['quota_id']}'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
            $quotainfo = $result->FetchRow();

            $quotasoutput .="<form action='{$scriptname}' method='post' class='form30'>
            <div class='header ui-widget-header'>".$clang->gT("Edit quota")."</div>
            <ul>
            <li>
            <label for ='quota_name'>".$clang->gT("Quota name").":</label> <input name='quota_name' id='quota_name' type='text' size='60' maxlength='255' value='{$quotainfo['name']}' />
            </li>
            <li>
            <label for ='quota_limit'>".$clang->gT("Quota limit").":</label> <input name='quota_limit' id='quota_limit' type='text' size='12' maxlength='9' value='{$quotainfo['qlimit']}' />
            </li></ul>";
            $langs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_push($langs,$baselang);

            require_once("../classes/inputfilter/class.inputfilter_clean.php");
            $myFilter = new InputFilter('','',1,1,1);

            $quotasoutput .= '
            <div class="tab-pane" id="tab-pane-quota-'.$surveyid.'">'."\n\n";
            foreach ($langs as $lang)
            {
                //Get this one
                $langquery = "SELECT * FROM ".db_table_name('quota_languagesettings')." WHERE quotals_quota_id='{$_POST['quota_id']}' AND quotals_language = '$lang'";
                $langresult = db_execute_assoc($langquery) or safe_die($connect->ErrorMsg());
                $langquotainfo = $langresult->FetchRow();
                $quotasoutput .= '
                <div class="tab-page">
                <h2 class="tab">'.GetLanguageNameFromCode($lang,false);
                if ($lang==$baselang) {$quotasoutput .= '('.$clang->gT("Base language").')';}
                $quotasoutput .= "</h2>";

                $quotasoutput.='
                <ul>
                <li>
                <label for="quotals_message_'.$lang.'">'.$clang->gT("Quota message").':</label>
                <textarea id="quotals_message_'.$lang.'" name="quotals_message_'.$lang.'" cols="60" rows="6">'.$langquotainfo['quotals_message'].'</textarea>
                </li>
                </ul>
                </div>';
            };
            $quotasoutput .= '
            <p><input name="submit" type="submit" value="'.$clang->gT("Save quota").'" />
            <input type="hidden" name="sid" value="'.$surveyid.'" />
            <input type="hidden" name="action" value="quotas" />
            <input type="hidden" name="subaction" value="modifyquota" />
            <input type="hidden" name="quota_id" value="'.$quotainfo['id'].'" />
            <button type="button" onclick="window.open(\''.$scriptname.'?action=quotas&amp;sid='.$surveyid.'\', \'_self\')">'.$clang->gT("Cancel").'</button>
            </div></form>';
        }

        $totalquotas=0;
        $totalcompleted=0;
        $csvoutput=array();
        if (($action == "quotas" && !isset($subaction)) || isset($viewquota))
        {

            $query = "SELECT * FROM ".db_table_name('quota')." , ".db_table_name('quota_languagesettings')."
            WHERE ".db_table_name('quota').".id = ".db_table_name('quota_languagesettings').".quotals_quota_id
            AND sid='".$surveyid."'
            AND quotals_language = '".$baselang."'
            ORDER BY name";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

            //create main quota <DIV> and headlines
            $quotasoutput .='<div class="header ui-widget-header">'.$clang->gT("Survey quotas").'</div>
            <br />
            <table id="quotalist" class="quotalist">
            <thead>
            <tr>
            <th width="20%">'.$clang->gT("Quota name").'</th>
            <th width="20%">'.$clang->gT("Status").'</th>
            <th width="30%">'.$clang->gT("Quota action").'</th>
            <th width="5%">'.$clang->gT("Completed").'</th>
            <th width="5%">'.$clang->gT("Limit").'</th>
            <th width="20%">'.$clang->gT("Action").'</th>
            </tr>
            </thead>';

            //NOTE: the footer always has to be put BEFORE the tbody tag!
            $quotasoutput .='
            <tfoot>
            <tr>
            <td>&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center" style="padding: 3px;"><input type="button" value="'.$clang->gT("Quick CSV report").'" onClick="window.open(\'admin.php?action=quotas&amp;sid='.$surveyid.'&amp;quickreport=y\', \'_self\')" /></td>
            </tr>
            </tfoot>
            <tbody>';

            //if there are quotas let's proceed
            if ($result->RecordCount() > 0)
            {
                //loop through all quotas
                while ($quotalisting = $result->FetchRow())
                {
                    $quotasoutput .='
                    <tr>
                    <td colspan="6" style="background-color: #567081; height: 2px">
                    </td>
                    </tr>
                    <tr>
                    <td align="center">'.$quotalisting['name'].'</td>
                    <td align="center">';
                    if ($quotalisting['active'] == 1)
                    {
                        $quotasoutput .= '<font color="#48B150">'.$clang->gT("Active").'</font>';
                    } else {
                        $quotasoutput .= '<font color="#B73838">'.$clang->gT("Not Active").'</font>';
                    }
                    $quotasoutput .='
                    </td>
                    <td align="center">';
                    if ($quotalisting['action'] == 1)
                    {
                        $quotasoutput .= $clang->gT("Terminate survey");
                    } elseif ($quotalisting['action'] == 2) {
                        $quotasoutput .= $clang->gT("Terminate survey with warning");
                    }
                    $totalquotas+=$quotalisting['qlimit'];
                    $completed=get_quotaCompletedCount($surveyid, $quotalisting['id']);
                    $highlight=($completed >= $quotalisting['qlimit']) ? "" : "style='color: orange'"; //Incomplete quotas displayed in red
                    $totalcompleted=$totalcompleted+$completed;
                    $csvoutput[]=$quotalisting['name'].",".$quotalisting['qlimit'].",".$completed.",".($quotalisting['qlimit']-$completed)."\r\n";

                    $quotasoutput .='
                    </td>
                    <td align="center" '.$highlight.'>'.$completed.'</td>
                    <td align="center">'.$quotalisting['qlimit'].'</td>
                    <td align="center" style="padding: 3px;">';
                    if (bHasSurveyPermission($surveyid, 'quotas','update'))
                    {
                        $quotasoutput .='<form action="'.$scriptname.'" method="post">
                        <input name="submit" type="submit" class="submit" value="'.$clang->gT("Edit").'" />
                        <input type="hidden" name="sid" value="'.$surveyid.'" />
                        <input type="hidden" name="action" value="quotas" />
                        <input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
                        <input type="hidden" name="subaction" value="quota_editquota" />
                        </form>';
                    }
                    if (bHasSurveyPermission($surveyid, 'quotas','delete'))
                    {
                        $quotasoutput .='<form action="'.$scriptname.'" method="post">
                        <input name="submit" type="submit" class="submit" value="'.$clang->gT("Remove").'" />
                        <input type="hidden" name="sid" value="'.$surveyid.'" />
                        <input type="hidden" name="action" value="quotas" />
                        <input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
                        <input type="hidden" name="subaction" value="quota_delquota" />
                        </form>';
                    }
                    $quotasoutput .='</td>
                    </tr>';

                    //headline for quota sub-parts
                    $quotasoutput .='
                    <tr class="evenrow">
                    <td align="center">&nbsp;</td>
                    <td align="center"><strong>'.$clang->gT("Question").'</strong></td>
                    <td align="center"><strong>'.$clang->gT("Answer").'</strong></td>
                    <td align="center">&nbsp;</td>
                    <td align="center">&nbsp;</td>
                    <td style="padding: 3px;" align="center">';
                    if (bHasSurveyPermission($surveyid, 'quotas','update'))
                    {
                        $quotasoutput .='<form action="'.$scriptname.'" method="post">
                        <input name="submit" type="submit" class="quota_new" value="'.$clang->gT("Add Answer").'" />
                        <input type="hidden" name="sid" value="'.$surveyid.'" />
                        <input type="hidden" name="action" value="quotas" />
                        <input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
                        <input type="hidden" name="subaction" value="new_answer" />
                        </form>';
                    }
                    $quotasoutput .='</td>
                    </tr>';

                    //check how many sub-elements exist for a certain quota
                    $query = "SELECT id,code,qid FROM ".db_table_name('quota_members')." where quota_id='".$quotalisting['id']."'";
                    $result2 = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

                    if ($result2->RecordCount() > 0)
                    {
                        //loop through all sub-parts
                        while ($quota_questions = $result2->FetchRow())
                        {
                            $question_answers = getQuotaAnswers($quota_questions['qid'],$surveyid,$quotalisting['id']);
                            $quotasoutput .='
                            <tr class="evenrow">
                            <td align="center">&nbsp;</td>
                            <td align="center">'.$question_answers[$quota_questions['code']]['Title'].'</td>
                            <td align="center">'.$question_answers[$quota_questions['code']]['Display'].'</td>
                            <td align="center">&nbsp;</td>
                            <td align="center">&nbsp;</td>
                            <td style="padding: 3px;" align="center">
                            <form action="'.$scriptname.'" method="post">
                            <input name="submit" type="submit" class="submit" value="'.$clang->gT("Remove").'" />
                            <input type="hidden" name="sid" value="'.$surveyid.'" />
                            <input type="hidden" name="action" value="quotas" />
                            <input type="hidden" name="quota_member_id" value="'.$quota_questions['id'].'" />
                            <input type="hidden" name="quota_qid" value="'.$quota_questions['qid'].'" />
                            <input type="hidden" name="quota_anscode" value="'.$quota_questions['code'].'" />
                            <input type="hidden" name="subaction" value="quota_delans" />
                            </form>
                            </td>
                            </tr>';
                        }
                    }

                }

            }
            else
            {
                $quotasoutput .='
                <tr>
                <td colspan="6" align="center">'.$clang->gT("No quotas have been set for this survey").'.</td>
                </tr>';
            }

            $quotasoutput .='
            <tr>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">'.$totalcompleted.'</td>
            <td align="center">'.$totalquotas.'</td>
            <td align="center" style="padding: 3px;">';
            if (bHasSurveyPermission($surveyid, 'quotas','create'))
            {
                $quotasoutput .='<form action="'.$scriptname.'" method="post">
                <input name="submit" type="submit" class="quota_new" value="'.$clang->gT("Add New Quota").'" />
                <input type="hidden" name="sid" value="'.$surveyid.'" />
                <input type="hidden" name="action" value="quotas" />
                <input type="hidden" name="subaction" value="new_quota" />
                </form>';

            }
            $quotasoutput .='</td>
            </tr>
            </tbody>
            </table>';
        }

        if(isset($_GET['quickreport']) && $_GET['quickreport'])
        {
            header("Content-Disposition: attachment; filename=results-survey".$surveyid.".csv");
            header("Content-type: text/comma-separated-values; charset=UTF-8");
            header("Pragma: public");
            echo $clang->gT("Quota name").",".$clang->gT("Limit").",".$clang->gT("Completed").",".$clang->gT("Remaining")."\r\n";
            foreach($csvoutput as $line)
            {
                echo $line;
            }
            die;
        }
        if(($subaction == "new_answer" || ($subaction == "new_answer_two" && !isset($_POST['quota_qid']))) && bHasSurveyPermission($surveyid,'quotas','create'))
        {
            if ($subaction == "new_answer_two") $_POST['quota_id'] = $_POST['quota_id'];

            $allowed_types = "(type ='G' or type ='M' or type ='Y' or type ='A' or type ='B' or type ='I' or type = 'L' or type='O' or type='!')";

            $query = "SELECT name FROM ".db_table_name('quota')." WHERE id='".$_POST['quota_id']."'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
            while ($quotadetails = $result->FetchRow())
            {
                $quota_name=$quotadetails['name'];
            }

            $query = "SELECT qid, title, question FROM ".db_table_name('questions')."q JOIN ".db_table_name('groups')."g on g.gid=q.gid WHERE {$allowed_types} AND g.sid={$surveyid} AND q.language='{$baselang}' AND g.language='{$baselang}' order by group_order, question_order";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
            if ($result->RecordCount() == 0)
            {
                $quotasoutput .="<div class=\"header\">".$clang->gT("Add Answer").": ".$clang->gT("Question Selection")."</div><br />
                <div class=\"messagebox\">
                ".$clang->gT("Sorry there are no supported question types in this survey.")."
                <br/><br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=quotas&amp;sid=$surveyid', '_self')\" value=\"".$clang->gT("Continue")."\"/>
                </div>";
            } else
            {
                $quotasoutput .='<div class="header ui-widget-header">'.$clang->gT("Survey Quota").': '.$clang->gT("Add Answer").'</div><br />
                <div class="messagebox ui-corner-all" style="width: 600px">
                <form action="'.$scriptname.'" class="form30" method="post">
                <div class="header ui-widget-header" >'.sprintf($clang->gt("New Answer for Quota '%s'"), $quota_name).'</div>
                <ul>
                <li>
                <label for="quota_qid">'.$clang->gT("Select Question").':</label>
                <select id="quota_qid" name="quota_qid" size="15">';

                while ($questionlisting = $result->FetchRow())
                {
                    $quotasoutput .='<option value="'.$questionlisting['qid'].'">'.$questionlisting['title'].': '.strip_tags(substr($questionlisting['question'],0,40)).'</option>';
                }

                $quotasoutput .='
                </select>
                </li>
                </ul>
                <p>
                <input name="submit" type="submit" class="submit" value="'.$clang->gT("Next").'" />
                <input type="hidden" name="sid" value="'.$surveyid.'" />
                <input type="hidden" name="action" value="quotas" />
                <input type="hidden" name="subaction" value="new_answer_two" />
                <input type="hidden" name="quota_id" value="'.$_POST['quota_id'].'" />

                </form>
                </div>';
            }
        }

        if($subaction == "new_answer_two" && isset($_POST['quota_qid']) && bHasSurveyPermission($surveyid, 'quotas','create'))
        {
            array_walk( $_POST, 'db_quote', true);

            $query = "SELECT name FROM ".db_table_name('quota')." WHERE id='".$_POST['quota_id']."'";
            $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
            while ($quotadetails = $result->FetchRow())
            {
                $quota_name=$quotadetails['name'];
            }

            $question_answers = getQuotaAnswers($_POST['quota_qid'],$surveyid,$_POST['quota_id']);
            $x=0;

            foreach ($question_answers as $qacheck)
            {
                if (isset($qacheck['rowexists'])) $x++;
            }

            reset($question_answers);

            if (count($question_answers) == $x)
            {
                $quotasoutput .="<div class=\"header\">".$clang->gT("Add Answer").": ".$clang->gT("Question Selection")."</div><br />
                <div class=\"messagebox\">
                ".$clang->gT("All answers are already selected in this quota.")."
                <br/><br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=quotas&amp;sid=$surveyid', '_self')\" value=\"".$clang->gT("Continue")."\"/>
                </div>";
            } else
            {
                $quotasoutput .='<div class="header ui-widget-header">'.$clang->gT("Survey Quota").': '.$clang->gT("Add Answer").'</div><br />
                <div class="messagebox ui-corner-all">
                <form action="'.$scriptname.'#quota_'.$_POST['quota_id'].'" class="form30" method="post">
                <div class="header">'.sprintf($clang->gt("New Answer for Quota '%s'"), $quota_name).'</div>
                <ul>
                <li><label for="quota_anscode">'.$clang->gT("Select Answer").':</label>
                <select id="quota_anscode" name="quota_anscode" size="15">';

                while (list($key,$value) = each($question_answers))
                {
                    if (!isset($value['rowexists'])) $quotasoutput .='<option value="'.$key.'">'.$key.':'.FlattenText(substr($value['Display'],0,40)).'</option>';
                }


                $quotasoutput .='
                </select>
                </li>
                <li>
                <label for="createanother">'.$clang->gT("Save this, then create another:").'</label>
                <input type="checkbox" id="createanother" name="createanother">
                </li>
                </ul><p>
                <input name="submit" type="submit" class="submit" value="'.$clang->gT("Next").'" />
                <input type="hidden" name="sid" value="'.$surveyid.'" />
                <input type="hidden" name="action" value="quotas" />
                <input type="hidden" name="subaction" value="insertquotaanswer" />
                <input type="hidden" name="quota_qid" value="'.$_POST['quota_qid'].'" />
                <input type="hidden" name="quota_id" value="'.$_POST['quota_id'].'" />
                </form>
                </div>';
            }
        }

        if ($subaction == "new_quota" && bHasSurveyPermission($surveyid, 'quotas','create'))
        {
            $quotasoutput.="<div class='header ui-widget-header'>".$clang->gT("New quota").'</div>';
            $quotasoutput.='<form class="form30" action="'.$scriptname.'" method="post" id="addnewquotaform" name="addnewquotaform">';
            $quotasoutput.='<ul>
            <li>
            <label for="quota_name">'.$clang->gT("Quota name").':</label>
            <input id="quota_name" name="quota_name" type="text" size="30" maxlength="255" />
            </li>
            <li>
            <label for="quota_limit">'.$clang->gT("Quota limit").':</label>
            <input id="quota_limit" name="quota_limit" type="text" size="12" maxlength="8" />
            </li>
            </ul>
            ';

            $langs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_push($langs,$baselang);

            require_once("../classes/inputfilter/class.inputfilter_clean.php");
            $myFilter = new InputFilter('','',1,1,1);

            $thissurvey=getSurveyInfo($surveyid);

            $quotasoutput .= '
            <div class="tab-pane" id="tab-pane-quota-'.$surveyid.'">'."\n\n";
            foreach ($langs as $lang)
            {
                $quotasoutput .= '
                <div class="tab-page">
                <h2 class="tab">'.GetLanguageNameFromCode($lang,false);
                if ($lang==$baselang) {$quotasoutput .= '('.$clang->gT("Base language").')';}
                $quotasoutput .= "</h2>";
                $quotasoutput.='
                <ul>
                <li>
                <label for="quotals_message_'.$lang.'">'.$clang->gT("Quota message").':</label>
                <textarea id="quotals_message_'.$lang.'" name="quotals_message_'.$lang.'" cols="60" rows="6">'.$clang->gT("Sorry your responses have exceeded a quota on this survey.").'</textarea>
                </li>
                </ul>
                </div>';
            };

            $quotasoutput .= '
            <input type="hidden" name="sid" value="'.$surveyid.'" />
            <input type="hidden" name="action" value="quotas" />
            <input type="hidden" name="subaction" value="insertquota" />
            </div>
            <p><input name="submit" type="submit" value="'.$clang->gT("Add New Quota").'" />
            </form>';
        }
    }

?>
