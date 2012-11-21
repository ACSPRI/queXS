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
 * $Id: database.php 12242 2012-01-27 23:41:13Z c_schmitz $
 */
//Last security audit on 2009-10-11

//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

if (!isset($action)) {$action=returnglobal('action');}
$postsid=returnglobal('sid');
$postgid=returnglobal('gid');
$postqid=returnglobal('qid');
$postqaid=returnglobal('qaid');

if (get_magic_quotes_gpc())
{$_POST  = array_map('recursive_stripslashes', $_POST);}



/**
 * Gets the maximum question_order field value for a group
 *
 * @param mixed $gid  The id of the group
 * @return mixed
 */
function get_max_question_order($gid)
{
    global $connect ;
    global $dbprefix ;
    $query="SELECT MAX(question_order) as maxorder FROM {$dbprefix}questions where gid=".$gid ;
    // echo $query;
    $result = db_execute_assoc($query);  // Checked
    $gv = $result->FetchRow();
    return $gv['maxorder'];
}

$databaseoutput ='';

if(isset($surveyid))
{
    if ($action == "insertquestiongroup" && bHasSurveyPermission($surveyid, 'surveycontent','create'))
    {
        $grplangs = GetAdditionalLanguagesFromSurveyID($postsid);
        $baselang = GetBaseLanguageFromSurveyID($postsid);
        $grplangs[] = $baselang;
        $errorstring = '';
        foreach ($grplangs as $grouplang)
        {
            if (!$_POST['group_name_'.$grouplang]) { $errorstring.= GetLanguageNameFromCode($grouplang,false)."\\n";}
        }
        if ($errorstring!='')
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Group could not be added.\\n\\nIt is missing the group name for the following languages","js").":\\n".$errorstring."\")\n //-->\n</script>\n";
        }

        else
        {
            $first=true;
            require_once("../classes/inputfilter/class.inputfilter_clean.php");
            $myFilter = new InputFilter('','',1,1,1);

            foreach ($grplangs as $grouplang)
            {
                //Clean XSS
                if ($filterxsshtml)
                {
                    $_POST['group_name_'.$grouplang]=$myFilter->process(html_entity_decode($_POST['group_name_'.$grouplang], ENT_QUOTES, "UTF-8"));
                    $_POST['description_'.$grouplang]=$myFilter->process(html_entity_decode($_POST['description_'.$grouplang], ENT_QUOTES, "UTF-8"));
                }
                else
                {
                    $_POST['group_name_'.$grouplang] = html_entity_decode($_POST['group_name_'.$grouplang], ENT_QUOTES, "UTF-8");
                    $_POST['description_'.$grouplang] = html_entity_decode($_POST['description_'.$grouplang], ENT_QUOTES, "UTF-8");
                }

                // Fix bug with FCKEditor saving strange BR types
                $_POST['group_name_'.$grouplang]=fix_FCKeditor_text($_POST['group_name_'.$grouplang]);
                $_POST['description_'.$grouplang]=fix_FCKeditor_text($_POST['description_'.$grouplang]);
                $grelevance = (isset($_POST['grelevance']) ? $_POST['grelevance'] : 1);


                if ($first)
                {
                    $query = "INSERT INTO ".db_table_name('groups')." (sid, group_name, description, grelevance, group_order, language) VALUES ('".db_quote($postsid)."', '".db_quote($_POST['group_name_'.$grouplang])."', '".db_quote($_POST['description_'.$grouplang])."','".db_quote($grelevance)."',".getMaxgrouporder(returnglobal('sid')).",'{$grouplang}')";
                    $result = $connect->Execute($query); // Checked
                    $groupid=$connect->Insert_Id(db_table_name_nq('groups'),"gid");
                    $first=false;
                }
                else{
                    db_switchIDInsert('groups',true);
                    $query = "INSERT INTO ".db_table_name('groups')." (gid, sid, group_name, description, grelevance, group_order, language) VALUES ('{$groupid}','".db_quote($postsid)."', '".db_quote($_POST['group_name_'.$grouplang])."', '".db_quote($_POST['description_'.$grouplang])."','".db_quote($grelevance)."',".getMaxgrouporder(returnglobal('sid')).",'{$grouplang}')";
                    $result = $connect->Execute($query) or safe_die("Error<br />".$query."<br />".$connect->ErrorMsg());   // Checked
                    db_switchIDInsert('groups',false);
                }
                if (!$result)
                {
                    $databaseoutput .= $clang->gT("Error: The database reported the following error:")."<br />\n";
                    $databaseoutput .= "<font color='red'>" . htmlspecialchars($connect->ErrorMsg()) . "</font>\n";
                    $databaseoutput .= "<pre>".htmlspecialchars($query)."</pre>\n";
                    $databaseoutput .= "</body>\n</html>";
                    exit;
                }
            }
            // This line sets the newly inserted group as the new group
            if (isset($groupid)){$gid=$groupid;}
            $_SESSION['flashmessage'] = $clang->gT("New question group was saved.");

        }
    }

    elseif ($action == "updategroup" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {
        $grplangs = GetAdditionalLanguagesFromSurveyID($postsid);
        $baselang = GetBaseLanguageFromSurveyID($postsid);
        array_push($grplangs,$baselang);
        require_once("../classes/inputfilter/class.inputfilter_clean.php");
        $myFilter = new InputFilter('','',1,1,1);
        foreach ($grplangs as $grplang)
        {
            if (isset($grplang) && $grplang != "")
            {
                if ($filterxsshtml)
                {
                    $_POST['group_name_'.$grplang]=$myFilter->process($_POST['group_name_'.$grplang]);
                    $_POST['description_'.$grplang]=$myFilter->process($_POST['description_'.$grplang]);
                }
                else
                {
                    $_POST['group_name_'.$grplang] = html_entity_decode($_POST['group_name_'.$grplang], ENT_QUOTES, "UTF-8");
                    $_POST['description_'.$grplang] = html_entity_decode($_POST['description_'.$grplang], ENT_QUOTES, "UTF-8");
                }

                // Fix bug with FCKEditor saving strange BR types
                $_POST['group_name_'.$grplang]=fix_FCKeditor_text($_POST['group_name_'.$grplang]);
                $_POST['description_'.$grplang]=fix_FCKeditor_text($_POST['description_'.$grplang]);

                // don't use array_map db_quote on POST
                // since this is iterated for each language
                //$_POST  = array_map('db_quote', $_POST);
                $ugquery = "UPDATE ".db_table_name('groups')." SET group_name='".db_quote($_POST['group_name_'.$grplang])."', description='".db_quote($_POST['description_'.$grplang])."', grelevance='".db_quote($_POST['grelevance'])."' WHERE sid=".db_quote($postsid)." AND gid=".db_quote($postgid)." AND language='{$grplang}'";
                $ugresult = $connect->Execute($ugquery);  // Checked
                if ($ugresult)
                {
                    $groupsummary = getgrouplist($postgid);
                }
                else
                {
                    $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Group could not be updated","js")."\")\n //-->\n</script>\n";
                    exit;
                }
            }
        }
        $_SESSION['flashmessage'] = $clang->gT("Question group successfully saved.");
    }


    elseif ($action == "delgroup" && bHasSurveyPermission($surveyid, 'surveycontent','delete'))
    {
        LimeExpressionManager::RevertUpgradeConditionsToRelevance($surveyid);

        if (!isset($gid)) $gid=returnglobal('gid');
        $query = "SELECT qid FROM ".db_table_name('groups')." g, ".db_table_name('questions')." q WHERE g.gid=q.gid AND g.gid=$gid AND q.parent_qid=0 group by qid";
        if ($result = db_execute_assoc($query)) // Checked
        {
            while ($row=$result->FetchRow())
            {
                $connect->Execute("DELETE FROM {$dbprefix}conditions WHERE qid={$row['qid']}");    // Checked
                $connect->Execute("DELETE FROM {$dbprefix}question_attributes WHERE qid={$row['qid']}"); // Checked
                $connect->Execute("DELETE FROM {$dbprefix}answers WHERE qid={$row['qid']}"); // Checked
                $connect->Execute("DELETE FROM {$dbprefix}questions WHERE qid={$row['qid']} or parent_qid={$row['qid']}"); // Checked
                $connect->Execute("DELETE FROM {$dbprefix}defaultvalues WHERE qid={$row['qid']}"); // Checked
                $connect->Execute("DELETE FROM {$dbprefix}quota_members WHERE qid={$qid}");
            }
        }
        $query = "DELETE FROM ".db_table_name('assessments')." WHERE sid=$surveyid AND gid=$gid";
        $result = $connect->Execute($query) or safe_die($connect->ErrorMsg());  // Checked

        $query = "DELETE FROM ".db_table_name('groups')." WHERE sid=$surveyid AND gid=$gid";
        $result = $connect->Execute($query) or safe_die($connect->ErrorMsg());  // Checked
        if ($result)
        {
            $gid = "";
            $groupselect = getgrouplist($gid);
            fixSortOrderGroups($surveyid);
            $_SESSION['flashmessage'] = $clang->gT("The question group was deleted.");
        }
        else
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Group could not be deleted","js")."\n$error\")\n //-->\n</script>\n";
        }
        LimeExpressionManager::UpgradeConditionsToRelevance($surveyid);
    }

    elseif ($action == "insertquestion" && bHasSurveyPermission($surveyid, 'surveycontent','create'))
    {
        $baselang = GetBaseLanguageFromSurveyID($postsid);
        if (strlen($_POST['title']) < 1)
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n "
            ."alert(\"".$clang->gT("The question could not be added. You must enter at least enter a question code.","js")."\")\n "
            ."//-->\n</script>\n";
        }
        else
        {
            if (!isset($_POST['lid']) || $_POST['lid'] == '') {$_POST['lid']="0";}
            if (!isset($_POST['lid1']) || $_POST['lid1'] == '') {$_POST['lid1']="0";}
            if(!empty($_POST['questionposition']) || $_POST['questionposition'] == '0')
            {
                //Bug Fix: remove +1 ->  $question_order=(sanitize_int($_POST['questionposition'])+1);
                $question_order=(sanitize_int($_POST['questionposition']));
                //Need to renumber all questions on or after this
                $cdquery = "UPDATE ".db_table_name('questions')." SET question_order=question_order+1 WHERE gid=".$postgid." AND question_order >= ".$question_order;
                $cdresult=$connect->Execute($cdquery) or safe_die($connect->ErrorMsg());  // Checked
            } else {
                $question_order=(getMaxquestionorder($postgid));
                $question_order++;
            }

            if ($filterxsshtml)
            {
                require_once("../classes/inputfilter/class.inputfilter_clean.php");
                $myFilter = new InputFilter('','',1,1,1);
                $_POST['title']=$myFilter->process($_POST['title']);
                $_POST['question_'.$baselang]=$myFilter->process($_POST['question_'.$baselang]);
                $_POST['help_'.$baselang]=$myFilter->process($_POST['help_'.$baselang]);
            }
            else
            {
                $_POST['title'] = html_entity_decode($_POST['title'], ENT_QUOTES, "UTF-8");
                $_POST['question_'.$baselang] = html_entity_decode($_POST['question_'.$baselang], ENT_QUOTES, "UTF-8");
                $_POST['help_'.$baselang] = html_entity_decode($_POST['help_'.$baselang], ENT_QUOTES, "UTF-8");
            }

            // Fix bug with FCKEditor saving strange BR types
            $_POST['title']=fix_FCKeditor_text($_POST['title']);
            $_POST['question_'.$baselang]=fix_FCKeditor_text($_POST['question_'.$baselang]);
            $_POST['help_'.$baselang]=fix_FCKeditor_text($_POST['help_'.$baselang]);

            $_POST  = array_map('db_quote', $_POST);
            $query = "INSERT INTO ".db_table_name('questions')." (sid, gid, type, title, question, preg, help, other, mandatory, question_order, relevance, language)"
            ." VALUES ('{$postsid}', '{$postgid}', '{$_POST['type']}', '{$_POST['title']}',"
            ." '{$_POST['question_'.$baselang]}', '{$_POST['preg']}', '{$_POST['help_'.$baselang]}', '{$_POST['other']}', '{$_POST['mandatory']}', $question_order,'".db_quote($_POST['relevance'])."','{$baselang}')";
            $result = $connect->Execute($query);  // Checked
            // Get the last inserted questionid for other languages
            $qid=$connect->Insert_ID(db_table_name_nq('questions'),"qid");

            // Add other languages
            if ($result)
            {
                $addlangs = GetAdditionalLanguagesFromSurveyID($postsid);
                foreach ($addlangs as $alang)
                {
                    if ($alang != "")
                    {
                        db_switchIDInsert('questions',true);
                        $query = "INSERT INTO ".db_table_name('questions')." (qid, sid, gid, type, title, question, preg, help, other, mandatory, question_order, language)"
                        ." VALUES ('$qid','{$postsid}', '{$postgid}', '{$_POST['type']}', '{$_POST['title']}',"
                        ." '{$_POST['question_'.$alang]}', '{$_POST['preg']}', '{$_POST['help_'.$alang]}', '{$_POST['other']}', '{$_POST['mandatory']}', $question_order,'{$alang}')";
                        $result2 = $connect->Execute($query);  // Checked
                        if (!$result2)
                        {
                            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".sprintf($clang->gT("Question in language %s could not be created.","js"),$alang)."\\n".$connect->ErrorMsg()."\")\n //-->\n</script>\n";

                        }
                        db_switchIDInsert('questions',false);
                }
                }
            }


            if (!$result)
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be created.","js")."\\n".$connect->ErrorMsg()."\")\n //-->\n</script>\n";

            }

            $qattributes=questionAttributes();
            $validAttributes=$qattributes[$_POST['type']];
            foreach ($validAttributes as $validAttribute)
            {
                if (isset($_POST[$validAttribute['name']]))
                {
                    if ($filterxsshtml)
                    {
                        $_POST[$validAttribute['name']]=$myFilter->process($_POST[$validAttribute['name']]);
                    }
                    $query = "INSERT into ".db_table_name('question_attributes')."
                              (qid, value, attribute) values ($qid,'".db_quote($_POST[$validAttribute['name']])."','{$validAttribute['name']}')";
                    $result = $connect->Execute($query) or safe_die("Error updating attribute value<br />".$query."<br />".$connect->ErrorMsg()); // Checked

                }
            }

            fixsortorderQuestions($postgid, $surveyid);
            $_SESSION['flashmessage'] = $clang->gT("Question was successfully added.");

            //include("surveytable_functions.php");
            //surveyFixColumns($surveyid);
        }
        LimeExpressionManager::SetDirtyFlag(); // so refreshes syntax highlighting
    }
    elseif ($action == "renumberquestions" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {
        //Automatically renumbers the "question codes" so that they follow
        //a methodical numbering method
        $style = ((isset($_POST['style']) && $_POST['style']=="bygroup") ? 'bygroup' : 'straight');
        $question_number=1;
        $group_number=0;
        $gseq=0;
        $gselect="SELECT a.qid, a.gid\n"
        ."FROM ".db_table_name('questions')." as a, ".db_table_name('groups')."\n"
        ."WHERE a.gid=".db_table_name('groups').".gid AND a.sid=$surveyid AND a.parent_qid=0 "
        ."GROUP BY a.gid, a.qid, ".db_table_name('groups').".group_order, question_order\n"
        ."ORDER BY ".db_table_name('groups').".group_order, question_order";
        $gresult=db_execute_assoc($gselect) or safe_die ("Error: ".$connect->ErrorMsg());  // Checked
        $grows = array(); //Create an empty array in case FetchRow does not return any rows
        while ($grow = $gresult->FetchRow()) {$grows[] = $grow;} // Get table output into array
        foreach($grows as $grow)
        {
            //Go through all the questions
            if ($style == 'bygroup' && (!isset($group_number) || $group_number != $grow['gid']))
            { //If we're doing this by group, restart the numbering when the group number changes
                $question_number=1;
                $group_number = $grow['gid'];
                $gseq++;
            }
            $usql="UPDATE ".db_table_name('questions')."\n"
            ."SET title='"
            .(($style == 'bygroup') ? ('G' . $gseq . '_') : '')
            ."Q".str_pad($question_number, 4, "0", STR_PAD_LEFT)."'\n"
            ."WHERE qid=".$grow['qid'];
            //$databaseoutput .= "[$sql]";
            $uresult=$connect->Execute($usql) or safe_die("Error: ".$connect->ErrorMsg());  // Checked
            $question_number++;
            $group_number=$grow['gid'];
        }
        $_SESSION['flashmessage'] = $clang->gT("Question codes were successfully regenerated.");
        LimeExpressionManager::SetDirtyFlag(); // so refreshes syntax highlighting
        }


    elseif ($action == "updatedefaultvalues" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {

        $questlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
        $baselang = GetBaseLanguageFromSurveyID($surveyid);
        array_unshift($questlangs,$baselang);

        // same_default value on/off for question
        $uqquery = "UPDATE ".db_table_name('questions');
        if (isset($_POST['samedefault']))
        {
            $uqquery .= "SET same_default = '1' ";
        }
        else
        {
            $uqquery .= "SET same_default = '0' ";
        }
        $uqquery .= "WHERE sid='".$postsid."' AND qid='".$postqid."'";
        $uqresult = $connect->Execute($uqquery) or safe_die ("Error Update Question: ".$uqquery."<br />".$connect->ErrorMsg());
        if (!$uqresult)
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be updated","js")."\n".$connect->ErrorMsg()."\")\n //-->\n</script>\n";
        }

        $questiontype=$connect->GetOne("SELECT type FROM ".db_table_name('questions')." WHERE qid=$postqid");
        $qtproperties=getqtypelist('','array');
        if ($qtproperties[$questiontype]['answerscales']>0 && $qtproperties[$questiontype]['subquestions']==0)
        {
            for ($scale_id=0;$scale_id<$qtproperties[$questiontype]['answerscales'];$scale_id++)
            {
                foreach ($questlangs as $language)
                {
                   if (isset($_POST['defaultanswerscale_'.$scale_id.'_'.$language]))
                   {
                       Updatedefaultvalues($postqid,0,$scale_id,'',$language,$_POST['defaultanswerscale_'.$scale_id.'_'.$language],true);
                   }
                   if (isset($_POST['other_'.$scale_id.'_'.$language]))
                   {
                       Updatedefaultvalues($postqid,0,$scale_id,'other',$language,$_POST['other_'.$scale_id.'_'.$language],true);
                   }
                }
            }
        }
        if ($qtproperties[$questiontype]['subquestions']>0)
        {

            foreach ($questlangs as $language)
            {
                $sqquery = "SELECT * FROM ".db_table_name('questions')." WHERE sid=$surveyid AND gid=$gid AND parent_qid=$postqid and language=".db_quoteall($language)." and scale_id=0 order by question_order";
                $sqresult = db_execute_assoc($sqquery);
                $sqrows = $sqresult->GetRows();

                for ($scale_id=0;$scale_id<$qtproperties[$questiontype]['subquestions'];$scale_id++)
                {
                   foreach ($sqrows as $aSubquestionrow)
                   {
                       if (isset($_POST['defaultanswerscale_'.$scale_id.'_'.$language.'_'.$aSubquestionrow['qid']]))
                       {
                           Updatedefaultvalues($postqid,$aSubquestionrow['qid'],$scale_id,'',$language,$_POST['defaultanswerscale_'.$scale_id.'_'.$language.'_'.$aSubquestionrow['qid']],true);
                       }
/*                       if (isset($_POST['other_'.$scale_id.'_'.$language]))
                       {
                           Updatedefaultvalues($postqid,$qid,$scale_id,'other',$language,$_POST['other_'.$scale_id.'_'.$language],true);
                       } */

                   }
                }
            }
        }
        if ($qtproperties[$questiontype]['answerscales']==0 && $qtproperties[$questiontype]['subquestions']==0)
        {
            foreach ($questlangs as $language)
            {
                if (isset($_POST['defaultanswerscale_0_'.$language.'_0']))
                {
                   Updatedefaultvalues($postqid,0,0,'',$language,$_POST['defaultanswerscale_0_'.$language.'_0'],true);
                }
            }
        }
        $_SESSION['flashmessage'] = $clang->gT("Default value settings were successfully saved.");
        LimeExpressionManager::SetDirtyFlag();
    }


    elseif ($action == "updatequestion" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {
        LimeExpressionManager::RevertUpgradeConditionsToRelevance($surveyid);

        $cqquery = "SELECT type, gid FROM ".db_table_name('questions')." WHERE qid={$postqid}";
        $cqresult=db_execute_assoc($cqquery) or safe_die ("Couldn't get question type to check for change<br />".$cqquery."<br />".$connect->ErrorMsg()); // Checked
        $cqr=$cqresult->FetchRow();
        $oldtype=$cqr['type'];
        $oldgid=$cqr['gid'];

        if ($filterxsshtml)
        {
            require_once("../classes/inputfilter/class.inputfilter_clean.php");
            $myFilter = new InputFilter('','',1,1,1);
        }

        // Remove invalid question attributes on saving
        $qattributes=questionAttributes();
        $attsql="delete from ".db_table_name('question_attributes')." where qid='{$postqid}' and ";
        if (isset($qattributes[$_POST['type']])){
            $validAttributes=$qattributes[$_POST['type']];
            foreach ($validAttributes as  $validAttribute)
            {
                $attsql.='attribute<>'.db_quoteall($validAttribute['name'])." and ";
            }
        }
        $attsql.='1=1';
        db_execute_assoc($attsql) or safe_die ("Couldn't delete obsolete question attributes<br />".$attsql."<br />".$connect->ErrorMsg()); // Checked


        //now save all valid attributes
        $validAttributes=$qattributes[$_POST['type']];
        foreach ($validAttributes as $validAttribute)
        {

            if (isset($_POST[$validAttribute['name']]))
            {

                if ($filterxsshtml)
                {
                    $sAttributeValue=$myFilter->process($_POST[$validAttribute['name']]);
                }
                else
                {
                    $sAttributeValue=$_POST[$validAttribute['name']];
                }
                if ($validAttribute['name']=='multiflexible_step' && trim($sAttributeValue)!='') {
                    $sAttributeValue=floatval($sAttributeValue);
                    if ($sAttributeValue==0) $sAttributeValue=1;
                };
                $query = "select qaid from ".db_table_name('question_attributes')."
                          WHERE attribute='".$validAttribute['name']."' AND qid=".$qid;
                $result = $connect->Execute($query) or safe_die("Error updating attribute value<br />".$query."<br />".$connect->ErrorMsg());  // Checked
                if ($result->Recordcount()>0)
                {
                    $query = "UPDATE ".db_table_name('question_attributes')."
                              SET value='".db_quote($sAttributeValue)."' WHERE attribute='".$validAttribute['name']."' AND qid=".$qid;
                    $result = $connect->Execute($query) or safe_die("Error updating attribute value<br />".$query."<br />".$connect->ErrorMsg());  // Checked
                }
                else
                {
                    $query = "INSERT into ".db_table_name('question_attributes')."
                              (qid, value, attribute) values ($qid,'".db_quote($sAttributeValue)."','{$validAttribute['name']}')";
                    $result = $connect->Execute($query) or safe_die("Error updating attribute value<br />".$query."<br />".$connect->ErrorMsg());  // Checked
                }
            }
        }


        $qtypes=getqtypelist('','array');
        // These are the questions types that have no answers and therefore we delete the answer in that case
        $iAnswerScales = $qtypes[$_POST['type']]['answerscales'];
        $iSubquestionScales = $qtypes[$_POST['type']]['subquestions'];

        // These are the questions types that have the other option therefore we set everything else to 'No Other'
        if (($_POST['type']!= "L") && ($_POST['type']!= "!") && ($_POST['type']!= "P") && ($_POST['type']!="M"))
        {
            $_POST['other']='N';
        }

        // These are the questions types that have no validation - so zap it accordingly
        if ($_POST['type']== "!" || $_POST['type']== "L" || $_POST['type']== "M" || $_POST['type']== "P" ||
        $_POST['type']== "F" || $_POST['type']== "H" ||
        $_POST['type']== "X" || $_POST['type']== "")
        {
            $_POST['preg']='';
        }

        // These are the questions types that have no mandatory property - so zap it accordingly
        if ($_POST['type']== "X" || $_POST['type']== "|")
        {
            $_POST['mandatory']='N';
        }


        if ($oldtype != $_POST['type'])
        {
            //Make sure there are no conditions based on this question, since we are changing the type
            $ccquery = "SELECT * FROM ".db_table_name('conditions')." WHERE cqid={$postqid}";
            $ccresult = db_execute_assoc($ccquery) or safe_die ("Couldn't get list of cqids for this question<br />".$ccquery."<br />".$connect->ErrorMsg()); // Checked
            $cccount=$ccresult->RecordCount();
            while ($ccr=$ccresult->FetchRow()) {$qidarray[]=$ccr['qid'];}
            if (isset($qidarray) && $qidarray) {$qidlist=implode(", ", $qidarray);}
        }
        if (isset($cccount) && $cccount)
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be updated. There are conditions for other questions that rely on the answers to this question and changing the type will cause problems. You must delete these conditions before you can change the type of this question.","js")." ($qidlist)\")\n //-->\n</script>\n";
        }
        else
        {
            if (isset($postgid) && $postgid != "")
            {

                    $questlangs = GetAdditionalLanguagesFromSurveyID($postsid);
                    $baselang = GetBaseLanguageFromSurveyID($postsid);
                    array_push($questlangs,$baselang);
                    if ($filterxsshtml)
                    {
                        $_POST['title']=$myFilter->process($_POST['title']);
                    }
                    else
                    {
                        $_POST['title'] = html_entity_decode($_POST['title'], ENT_QUOTES, "UTF-8");
                    }

                    // Fix bug with FCKEditor saving strange BR types
                    $_POST['title']=fix_FCKeditor_text($_POST['title']);

                    foreach ($questlangs as $qlang)
                    {
                        if ($filterxsshtml)
                        {
                            $_POST['question_'.$qlang]=$myFilter->process(html_entity_decode($_POST['question_'.$qlang], ENT_QUOTES, "UTF-8"));
                            $_POST['help_'.$qlang]=$myFilter->process(html_entity_decode($_POST['help_'.$qlang], ENT_QUOTES, "UTF-8"));
                        }
                        else
                        {
                            $_POST['question_'.$qlang] = html_entity_decode($_POST['question_'.$qlang], ENT_QUOTES, "UTF-8");
                            $_POST['help_'.$qlang] = html_entity_decode($_POST['help_'.$qlang], ENT_QUOTES, "UTF-8");
                        }
                        // Fix bug with FCKEditor saving strange BR types
                        $_POST['question_'.$qlang]=fix_FCKeditor_text($_POST['question_'.$qlang]);
                        $_POST['help_'.$qlang]=fix_FCKeditor_text($_POST['help_'.$qlang]);

                        if (isset($qlang) && $qlang != "")
                        { // ToDo: Sanitize the POST variables !
                            $uqquery = "UPDATE ".db_table_name('questions')
                            . "SET type='".db_quote($_POST['type'])."', title='".db_quote($_POST['title'])."', "
                            . "question='".db_quote($_POST['question_'.$qlang])."', preg='".db_quote($_POST['preg'])."', help='".db_quote($_POST['help_'.$qlang])."', "
                            . "gid='".db_quote($postgid)."', other='".db_quote($_POST['other'])."', "
                            . "mandatory='".db_quote($_POST['mandatory'])."'"
                            . ", relevance='".db_quote($_POST['relevance'])."'";;
                            if ($oldgid!=$postgid)
                            {
                                if ( getGroupOrder(returnglobal('sid'),$oldgid) > getGroupOrder(returnglobal('sid'),returnglobal('gid')) )
                                {
                                    // Moving question to a 'upper' group
                                    // insert question at the end of the destination group
                                    // this prevent breaking conditions if the target qid is in the dest group
                                    $insertorder = getMaxquestionorder($postgid) + 1;
                                    $uqquery .=', question_order='.$insertorder.' ';
                                }
                                else
                                {
                                    // Moving question to a 'lower' group
                                    // insert question at the beginning of the destination group
                                    shiftorderQuestions($postsid,$postgid,1); // makes 1 spare room for new question at top of dest group
                                    $uqquery .=', question_order=0 ';
                                }
                            }
                            $uqquery.= "WHERE sid='".$postsid."' AND qid='".$postqid."' AND language='{$qlang}'";
                            $uqresult = $connect->Execute($uqquery) or safe_die ("Error Update Question: ".$uqquery."<br />".$connect->ErrorMsg());  // Checked
                            if (!$uqresult)
                            {
                                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be updated","js")."\n".$connect->ErrorMsg()."\")\n //-->\n</script>\n";
                            }

                        }
                    }
                    // Update the group ID on subquestions, too
                    if ($oldgid!=$postgid)
                    {
                        $sQuery="UPDATE ".db_table_name('questions')." set gid={$postgid} where gid={$oldgid} and parent_qid>0";
                        $oResult = $connect->Execute($sQuery) or safe_die ("Error updating question group ID: ".$uqquery."<br />".$connect->ErrorMsg());  // Checked
                    }
                    // if the group has changed then fix the sortorder of old and new group
                    if ($oldgid!=$postgid)
                    {
                        fixsortorderQuestions($oldgid, $surveyid);
                        fixsortorderQuestions($postgid, $surveyid);
                        // If some questions have conditions set on this question's answers
                        // then change the cfieldname accordingly
                        fixmovedquestionConditions($postqid, $oldgid, $postgid);
                    }

                    $query = "DELETE FROM ".db_table_name('answers')." WHERE qid= {$postqid} and scale_id>={$iAnswerScales}";
                        $result = $connect->Execute($query) or safe_die("Error: ".$connect->ErrorMsg()); // Checked

                    // Remove old subquestion scales
                    $query = "DELETE FROM ".db_table_name('questions')." WHERE parent_qid={$postqid} and scale_id>={$iSubquestionScales}";
                        $result = $connect->Execute($query) or safe_die("Error: ".$connect->ErrorMsg()); // Checked
                    $_SESSION['flashmessage'] = $clang->gT("Question was successfully saved.");


            }
            else
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be updated","js")."\")\n //-->\n</script>\n";
            }
        }
        LimeExpressionManager::UpgradeConditionsToRelevance($surveyid);
    }

    elseif ($action == "copynewquestion" && bHasSurveyPermission($surveyid, 'surveycontent','create'))
    {

        if (!$_POST['title'])
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be added. You must insert a code in the mandatory field","js")."\")\n //-->\n</script>\n";
        }
        else
        {
            $questlangs = GetAdditionalLanguagesFromSurveyID($postsid);
            $baselang = GetBaseLanguageFromSurveyID($postsid);

            //Get maximum order from the question group
            $max=get_max_question_order($postgid)+1 ;
            // Insert the base language of the question
            if ($filterxsshtml)
            {
                require_once("../classes/inputfilter/class.inputfilter_clean.php");
                $myFilter = new InputFilter('','',1,1,1);
                // Prevent XSS attacks
                $_POST['title']=$myFilter->process($_POST['title']);
                $_POST['question_'.$baselang]=$myFilter->process($_POST['question_'.$baselang]);
                $_POST['help_'.$baselang]=$myFilter->process($_POST['help_'.$baselang]);
            }
            else
            {
                $_POST['title'] = html_entity_decode($_POST['title'], ENT_QUOTES, "UTF-8");
                $_POST['question_'.$baselang] = html_entity_decode($_POST['question_'.$baselang], ENT_QUOTES, "UTF-8");
                $_POST['help_'.$baselang] = html_entity_decode($_POST['help_'.$baselang], ENT_QUOTES, "UTF-8");
            }


            // Fix bug with FCKEditor saving strange BR types
            $_POST['title']=fix_FCKeditor_text($_POST['title']);
            $_POST['question_'.$baselang]=fix_FCKeditor_text($_POST['question_'.$baselang]);
            $_POST['help_'.$baselang]=fix_FCKeditor_text($_POST['help_'.$baselang]);
            $_POST  = array_map('db_quote', $_POST);
            $query = "INSERT INTO {$dbprefix}questions (sid, gid, type, title, question, preg, help, other, mandatory, relevance, question_order, language)
                      VALUES ({$postsid}, {$postgid}, '{$_POST['type']}', '{$_POST['title']}', '".$_POST['question_'.$baselang]."', '{$_POST['preg']}', '".$_POST['help_'.$baselang]."', '{$_POST['other']}', '{$_POST['mandatory']}', '{$_POST['relevance']}', $max,".db_quoteall($baselang).")";
            $result = $connect->Execute($query) or safe_die($connect->ErrorMsg()); // Checked
            $newqid = $connect->Insert_ID("{$dbprefix}questions","qid");
            if (!$result)
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be created.","js")."\\n".htmlspecialchars($connect->ErrorMsg())."\")\n //-->\n</script>\n";

            }

            foreach ($questlangs as $qlanguage)
            {
                if ($filterxsshtml)
                {
                    $_POST['question_'.$qlanguage]=$myFilter->process($_POST['question_'.$qlanguage]);
                    $_POST['help_'.$qlanguage]=$myFilter->process($_POST['help_'.$qlanguage]);
                }
                else
                {
                    $_POST['question_'.$qlanguage] = html_entity_decode($_POST['question_'.$qlanguage], ENT_QUOTES, "UTF-8");
                    $_POST['help_'.$qlanguage] = html_entity_decode($_POST['help_'.$qlanguage], ENT_QUOTES, "UTF-8");
                }

                // Fix bug with FCKEditor saving strange BR types
                $_POST['question_'.$qlanguage]=fix_FCKeditor_text($_POST['question_'.$qlanguage]);
                $_POST['help_'.$qlanguage]=fix_FCKeditor_text($_POST['help_'.$qlanguage]);

                db_switchIDInsert('questions',true);
                $query = "INSERT INTO {$dbprefix}questions (qid, sid, gid, type, title, question, help, other, mandatory, relevance, question_order, language)
                      VALUES ($newqid,{$postsid}, {$postgid}, '{$_POST['type']}', '{$_POST['title']}', '".$_POST['question_'.$qlanguage]."', '".$_POST['help_'.$qlanguage]."', '{$_POST['other']}', '{$_POST['mandatory']}', '{$_POST['relevance']}', $max,".db_quoteall($qlanguage).")";
                $result = $connect->Execute($query) or safe_die($connect->ErrorMsg()); // Checked
                db_switchIDInsert('questions',false);
            }
            if (!$result)
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be created.","js")."\\n".htmlspecialchars($connect->ErrorMsg())."\")\n //-->\n</script>\n";

            }
            if (returnglobal('copysubquestions') == "Y")
            {
                $aSQIDMappings=array();
                $q1 = "SELECT * FROM {$dbprefix}questions WHERE parent_qid="
                . returnglobal('oldqid')
                . " ORDER BY question_order";
                $r1 = db_execute_assoc($q1);  // Checked
                $tablename=$dbprefix.'questions';
                while ($qr1 = $r1->FetchRow())
                {
                    $qr1['parent_qid']=$newqid;
                    if (isset($aSQIDMappings[$qr1['qid']]))
                    {
                        $qr1['qid']=$aSQIDMappings[$qr1['qid']];
                        db_switchIDInsert('questions',true);
                    }
                    else
                    {
                        $oldqid=$qr1['qid'];
                        unset($qr1['qid']);
                    }
                    $qr1['gid']=$postgid;
                    $sInsertSQL = $connect->GetInsertSQL($tablename,$qr1);
                    $ir1 = $connect->Execute($sInsertSQL);   // Checked
                    if (isset($qr1['qid']))
                    {
                        db_switchIDInsert('questions',false);
                    }
                    else
                    {
                        $aSQIDMappings[$oldqid]=$connect->Insert_ID($tablename,"qid");
                    }

                }
            }

            if (returnglobal('copyanswers') == "Y")
            {
                $q1 = "SELECT * FROM {$dbprefix}answers WHERE qid="
                . returnglobal('oldqid')
                . " ORDER BY code";
                $r1 = db_execute_assoc($q1);  // Checked
                while ($qr1 = $r1->FetchRow())
                {
                    $qr1 = array_map('db_quote', $qr1);
                    $i1 = "INSERT INTO {$dbprefix}answers (qid, code, answer, sortorder, language, scale_id, assessment_value) "
                    . "VALUES ('$newqid', '{$qr1['code']}', "
                    . "'{$qr1['answer']}', "
                    . "'{$qr1['sortorder']}', '{$qr1['language']}', '{$qr1['scale_id']}', '{$qr1['assessment_value']}')";
                    $ir1 = $connect->Execute($i1);   // Checked

                }
            }
            if (returnglobal('copyattributes') == "Y")
            {
                $q1 = "SELECT * FROM {$dbprefix}question_attributes
                   WHERE qid=".returnglobal('oldqid')."
                   ORDER BY qaid";
                $r1 = db_execute_assoc($q1); // Checked
                while($qr1 = $r1->FetchRow())
                {
                    $qr1 = array_map('db_quote', $qr1);
                    $i1 = "INSERT INTO {$dbprefix}question_attributes
                       (qid, attribute, value)
                       VALUES ('$newqid',
                       '{$qr1['attribute']}',
                       '{$qr1['value']}')";
                    $ir1 = $connect->Execute($i1);   // Checked
                } // while
            }
            fixsortorderQuestions($postgid, $surveyid);
            $gid=$postgid; //Sets the gid so that admin.php displays whatever group was chosen for this copied question
            $qid=$newqid; //Sets the qid so that admin.php displays the newly created question
            $_SESSION['flashmessage'] = $clang->gT("Question was successfully copied.");

            LimeExpressionManager::SetDirtyFlag(); // so refreshes syntax highlighting
        }
    }
    elseif ($action == "delquestion" && bHasSurveyPermission($surveyid, 'surveycontent','delete'))
    {
        if (!isset($qid)) {$qid=returnglobal('qid');}
        //check if any other questions have conditions which rely on this question. Don't delete if there are.
        LimeExpressionManager::RevertUpgradeConditionsToRelevance(NULL,$qid);

        $ccquery = "SELECT * FROM {$dbprefix}conditions WHERE cqid=$qid";
        $ccresult = db_execute_assoc($ccquery) or safe_die ("Couldn't get list of cqids for this question<br />".$ccquery."<br />".$connect->ErrorMsg()); // Checked
        $cccount=$ccresult->RecordCount();
        while ($ccr=$ccresult->FetchRow()) {$qidarray[]=$ccr['qid'];}
        if (isset($qidarray)) {$qidlist=implode(", ", $qidarray);}
        if ($cccount) //there are conditions dependent on this question
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Question could not be deleted. There are conditions for other questions that rely on this question. You cannot delete this question until those conditions are removed","js")." ($qidlist)\")\n //-->\n</script>\n";
        }
        else
        {
            $gid = $connect->GetOne("SELECT gid FROM ".db_table_name('questions')." WHERE qid={$qid}"); // Checked

            //see if there are any conditions/attributes/answers/defaultvalues for this question, and delete them now as well
            $connect->Execute("DELETE FROM {$dbprefix}conditions WHERE qid={$qid}");    // Checked
            $connect->Execute("DELETE FROM {$dbprefix}question_attributes WHERE qid={$qid}"); // Checked
            $connect->Execute("DELETE FROM {$dbprefix}answers WHERE qid={$qid}"); // Checked
            $connect->Execute("DELETE FROM {$dbprefix}questions WHERE qid={$qid} or parent_qid={$qid}"); // Checked
            $connect->Execute("DELETE FROM {$dbprefix}defaultvalues WHERE qid={$qid}"); // Checked
            $connect->Execute("DELETE FROM {$dbprefix}quota_members WHERE qid={$qid}");
            fixsortorderQuestions($gid, $surveyid);

            $qid="";
            $postqid="";
            $_GET['qid']="";
        }
        $_SESSION['flashmessage'] = $clang->gT("Question was successfully deleted.");
    }

    elseif ($action == "updateansweroptions" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {

        $anslangs = GetAdditionalLanguagesFromSurveyID($surveyid);
        $baselang = GetBaseLanguageFromSurveyID($surveyid);

        $alllanguages = $anslangs;
        array_unshift($alllanguages,$baselang);


        $query = "select type from ".db_table_name('questions')." where qid=$qid";
        $questiontype = $connect->GetOne($query);    // Checked
        $qtypes=getqtypelist('','array');
        $scalecount=$qtypes[$questiontype]['answerscales'];

        $count=0;
        $invalidCode = 0;
        $duplicateCode = 0;

        require_once("../classes/inputfilter/class.inputfilter_clean.php");
        $myFilter = new InputFilter('','',1,1,1);

        //First delete all answers
        $query = "delete from ".db_table_name('answers')." where qid=".db_quote($qid);
        $result = $connect->Execute($query); // Checked

        LimeExpressionManager::RevertUpgradeConditionsToRelevance($surveyid);

        for ($scale_id=0;$scale_id<$scalecount;$scale_id++)
        {
            $maxcount=(int)$_POST['answercount_'.$scale_id];
            for ($sortorderid=1;$sortorderid<$maxcount;$sortorderid++)
            {
                $code=sanitize_paranoid_string($_POST['code_'.$sortorderid.'_'.$scale_id]);
                if (isset($_POST['oldcode_'.$sortorderid.'_'.$scale_id])) {
                    $oldcode=sanitize_paranoid_string($_POST['oldcode_'.$sortorderid.'_'.$scale_id]);
                    if($code !== $oldcode) {
                        $query='UPDATE '.db_table_name('conditions').' SET value='.db_quoteall($code).' WHERE cqid='.db_quote($qid).' AND value='.db_quoteall($oldcode);
                        $connect->execute($query);
                    }
                }
                $assessmentvalue=(int) $_POST['assessment_'.$sortorderid.'_'.$scale_id];
                foreach ($alllanguages as $language)
                {
                    $answer=$_POST['answer_'.$language.'_'.$sortorderid.'_'.$scale_id];
                    if ($filterxsshtml)
                    {
                        //Sanitize input, strip XSS
                        $answer=$myFilter->process($answer);
                    }
                    else
                    {
                        $answer=html_entity_decode($answer, ENT_QUOTES, "UTF-8");
                    }
                    // Fix bug with FCKEditor saving strange BR types
                    $answer=fix_FCKeditor_text($answer);

                    // Now we insert the answers
                    $query = "INSERT INTO ".db_table_name('answers')." (code,answer,qid,sortorder,language,assessment_value, scale_id)
                              VALUES (".db_quoteall($code).", ".
                    db_quoteall($answer).", ".
                    db_quote($qid).", ".
                    db_quote($sortorderid).", ".
                    db_quoteall($language).", ".
                    db_quote($assessmentvalue).",
                    $scale_id)";
                    if (!$result = $connect->Execute($query)) // Checked
                    {
                        $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Failed to update answers","js")." - ".$query." - ".$connect->ErrorMsg()."\")\n //-->\n</script>\n";
                    }
                } // foreach ($alllanguages as $language)


            }  // for ($sortorderid=0;$sortorderid<$maxcount;$sortorderid++)
        }  //  for ($scale_id=0;

        LimeExpressionManager::UpgradeConditionsToRelevance($surveyid);

        if ($invalidCode == 1) $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Answers with a code of 0 (zero) or blank code are not allowed, and will not be saved","js")."\")\n //-->\n</script>\n";
        if ($duplicateCode == 1) $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Duplicate codes found, these entries won't be updated","js")."\")\n //-->\n</script>\n";

        $sortorderid--;
        $_SESSION['flashmessage'] = $clang->gT("Answer options were successfully saved.");

        $action='editansweroptions';

    }

    elseif ($action == "updatesubquestions" && bHasSurveyPermission($surveyid, 'surveycontent','update'))
    {

        $anslangs = GetAdditionalLanguagesFromSurveyID($surveyid);
        $baselang = GetBaseLanguageFromSurveyID($surveyid);
        array_unshift($anslangs,$baselang);

        $query = "select type from ".db_table_name('questions')." where qid=$qid";
        $questiontype = $connect->GetOne($query);    // Checked
        $qtypes=getqtypelist('','array');
        $scalecount=$qtypes[$questiontype]['subquestions'];

        // First delete any deleted ids
        $deletedqids=explode(' ', trim($_POST['deletedqids']));

        LimeExpressionManager::RevertUpgradeConditionsToRelevance($surveyid);

        foreach ($deletedqids as $deletedqid)
        {
            $deletedqid=(int)$deletedqid;
            if ($deletedqid>0)
            { // don't remove undefined
            $query = "DELETE FROM ".db_table_name('questions')." WHERE qid='{$deletedqid}'";  // Checked
            if (!$result = $connect->Execute($query))
            {
                $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Failed to delete answer","js")." - ".$query." - ".$connect->ErrorMsg()."\")\n //-->\n</script>\n";
            }
        }
        }

        //Determine ids by evaluating the hidden field
        $rows=array();
        $codes=array();
        $oldcodes=array();
        foreach ($_POST as $postkey=>$postvalue)
        {
            $postkey=explode('_',$postkey);
            if ($postkey[0]=='answer')
            {
                $rows[$postkey[3]][$postkey[1]][$postkey[2]]=$postvalue;
            }
            if ($postkey[0]=='code')
            {
                $codes[$postkey[2]][]=$postvalue;
            }
            if ($postkey[0]=='oldcode')
            {
                $oldcodes[$postkey[2]][]=$postvalue;
            }
        }
        $count=0;
        $invalidCode = 0;
        $duplicateCode = 0;
        $dupanswers = array();
        /*
         for ($scale_id=0;$scale_id<$scalecount;$scale_id++)
         {

         // Find duplicate codes and add these to dupanswers array
         $foundCat=array_count_values($codes);
         foreach($foundCat as $key=>$value){
         if($value>=2){
         $dupanswers[]=$key;
         }
         }
         }
         */
        require_once("../classes/inputfilter/class.inputfilter_clean.php");
        $myFilter = new InputFilter('','',1,1,1);


        //$insertqids=array(); //?
        $insertqid = array();
        for ($scale_id=0;$scale_id<$scalecount;$scale_id++)
        {
            foreach ($anslangs as $language)
            {
                $position=0;
                foreach ($rows[$scale_id][$language] as $subquestionkey=>$subquestionvalue)
                {
                    if (substr($subquestionkey,0,3)!='new')
                    {
                        $query='Update '.db_table_name('questions').' set question_order='.($position+1).', title='.db_quoteall($codes[$scale_id][$position]).', question='.db_quoteall($subquestionvalue).', scale_id='.$scale_id.' where qid='.db_quoteall($subquestionkey).' AND language='.db_quoteall($language);
                        $connect->execute($query);

                        if(isset($oldcodes[$scale_id][$position]) && $codes[$scale_id][$position] !== $oldcodes[$scale_id][$position]) {
                            $query='UPDATE '.db_table_name('conditions').' SET cfieldname="+'.$surveyid.'X'.$gid.'X'.$qid.db_quote($codes[$scale_id][$position]).'" WHERE cqid='.$qid.' AND cfieldname="+'.$surveyid.'X'.$gid.'X'.$qid.db_quote($oldcodes[$scale_id][$position]).'"';
                            $connect->execute($query);
                            $query='UPDATE '.db_table_name('conditions').' SET value="'.db_quote($codes[$scale_id][$position]).'" WHERE cqid='.$qid.' AND cfieldname="'.$surveyid.'X'.$gid.'X'.$qid.'" AND value="'.$oldcodes[$scale_id][$position].'"';
                            $connect->execute($query);
                        }

                    }
                    else
                    {
                        if (!isset($insertqid[$scale_id][$position]))
                        {
                            $query='INSERT into '.db_table_name('questions').' (sid, gid, question_order, title, question, parent_qid, language, scale_id) values ('.$surveyid.','.$gid.','.($position+1).','.db_quoteall($codes[$scale_id][$position]).','.db_quoteall($subquestionvalue).','.$qid.','.db_quoteall($language).','.$scale_id.')';
                            $connect->execute($query);
                            $insertqid[$scale_id][$position]=$connect->Insert_Id(db_table_name_nq('questions'),"qid");
                        }
                        else
                        {
                            db_switchIDInsert('questions',true);
                            $query='INSERT into '.db_table_name('questions').' (qid, sid, gid, question_order, title, question, parent_qid, language, scale_id) values ('.$insertqid[$scale_id][$position].','.$surveyid.','.$gid.','.($position+1).','.db_quoteall($codes[$scale_id][$position]).','.db_quoteall($subquestionvalue).','.$qid.','.db_quoteall($language).','.$scale_id.')';
                            $connect->execute($query);
                            db_switchIDInsert('questions',false);
                        }
                    }
                    $position++;
                }

            }
        }
        LimeExpressionManager::UpgradeConditionsToRelevance($surveyid);

        //include("surveytable_functions.php");
        //surveyFixColumns($surveyid);
        $_SESSION['flashmessage'] = $clang->gT("Subquestions were successfully saved.");

        $action='editsubquestions';
    }


    elseif (($action == "updatesurveysettingsandeditlocalesettings" || $action == "updatesurveysettings") && bHasSurveyPermission($surveyid,'surveysettings','update'))
    {

        $formatdata=getDateFormatData($_SESSION['dateformat']);
        if (trim($_POST['expires'])=="")
        {
            $_POST['expires']=null;
        }
        else
        {
            $datetimeobj = new Date_Time_Converter($_POST['expires'], $formatdata['phpdate'].' H:i');
            $_POST['expires']=$datetimeobj->convert("Y-m-d H:i:s");
        }
        if (trim($_POST['startdate'])=="")
        {
            $_POST['startdate']=null;
        }
        else
        {
            $datetimeobj = new Date_Time_Converter($_POST['startdate'],$formatdata['phpdate'].' H:i');
            $_POST['startdate']=$datetimeobj->convert("Y-m-d H:i:s");
        }

        //make sure only numbers are passed within the $_POST variable
        $_POST['tokenlength'] = (int) $_POST['tokenlength'];

        //token length has to be at least 5, otherwise set it to default (15)
        if($_POST['tokenlength'] < 5)
        {
            $_POST['tokenlength'] = 15;
        }

        CleanLanguagesFromSurvey($postsid,$_POST['languageids']);
        FixLanguageConsistency($postsid,$_POST['languageids']);

        if($_SESSION['USER_RIGHT_SUPERADMIN'] != 1 && $_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] != 1 && !hasTemplateManageRights($_SESSION['loginID'], $_POST['template'])) $_POST['template'] = "default";

        $sql = "SELECT * FROM {$dbprefix}surveys WHERE sid={$postsid}";  // We are using $dbrepfix here instead of db_table_name on purpose because GetUpdateSQL doesn't work correclty on Postfres with a quoted table name
        $rs = db_execute_assoc($sql); // Checked
        $updatearray= array('admin'=>$_POST['admin'],
                            'expires'=>$_POST['expires'],
                            'adminemail'=>$_POST['adminemail'],
                            'startdate'=>$_POST['startdate'],
                            'bounce_email'=>$_POST['bounce_email'],
                            'anonymized'=>$_POST['anonymized'],
                            'faxto'=>$_POST['faxto'],
                            'format'=>$_POST['format'],
                            'savetimings'=>$_POST['savetimings'],
                            'template'=>$_POST['template'],
                            'assessments'=>$_POST['assessments'],
                            'language'=>$_POST['language'],
                            'additional_languages'=>$_POST['languageids'],
                            'datestamp'=>$_POST['datestamp'],
                            'ipaddr'=>$_POST['ipaddr'],
                            'refurl'=>$_POST['refurl'],
                            'publicgraphs'=>$_POST['publicgraphs'],
                            'usecookie'=>$_POST['usecookie'],
                            'allowregister'=>$_POST['allowregister'],
                            'allowsave'=>$_POST['allowsave'],
                            'navigationdelay'=>$_POST['navigationdelay'],
                            'printanswers'=>$_POST['printanswers'],
                            'publicstatistics'=>$_POST['publicstatistics'],
                            'autoredirect'=>$_POST['autoredirect'],
                            'showxquestions'=>$_POST['showxquestions'],
                            'showgroupinfo'=>$_POST['showgroupinfo'],
                            'showqnumcode'=>$_POST['showqnumcode'],
                            'shownoanswer'=>$_POST['shownoanswer'],
                            'showwelcome'=>$_POST['showwelcome'],
                            'allowprev'=>$_POST['allowprev'],
                            'allowjumps'=>$_POST['allowjumps'],
                            'nokeyboard'=>$_POST['nokeyboard'],
                            'showprogress'=>$_POST['showprogress'],
                            'listpublic'=>$_POST['public'],
                            'htmlemail'=>$_POST['htmlemail'],
                            'tokenanswerspersistence'=>$_POST['tokenanswerspersistence'],
                            'alloweditaftercompletion'=>$_POST['alloweditaftercompletion'],
                            'usecaptcha'=>$_POST['usecaptcha'],
                            'emailresponseto'=>trim($_POST['emailresponseto']),
                            'emailnotificationto'=>trim($_POST['emailnotificationto']),
                            'googleanalyticsapikey'=>trim($_POST['googleanalyticsapikey']),
                            'googleanalyticsstyle'=>trim($_POST['googleanalyticsstyle']),
                            'tokenlength'=>$_POST['tokenlength']
        );

        $usquery=$connect->GetUpdateSQL($rs, $updatearray, false, get_magic_quotes_gpc());
        if ($usquery) {
            $usresult = $connect->Execute($usquery) or safe_die("Error updating<br />".$usquery."<br /><br /><strong>".$connect->ErrorMsg());  // Checked
        }
        $sqlstring ='';
        foreach (GetAdditionalLanguagesFromSurveyID($surveyid) as $langname)
        {
            if ($langname)
            {
                $sqlstring .= "and surveyls_language <> '".$langname."' ";
            }
        }
        // Add base language too
        $sqlstring .= "and surveyls_language <> '".GetBaseLanguageFromSurveyID($surveyid)."' ";

        $usquery = "Delete from ".db_table_name('surveys_languagesettings')." where surveyls_survey_id={$postsid} ".$sqlstring;
        $usresult = $connect->Execute($usquery) or safe_die("Error deleting obsolete surveysettings<br />".$usquery."<br /><br /><strong>".$connect->ErrorMsg()); // Checked

        foreach (GetAdditionalLanguagesFromSurveyID($surveyid) as $langname)
        {
            if ($langname)
            {
                $usquery = "select * from ".db_table_name('surveys_languagesettings')." where surveyls_survey_id={$postsid} and surveyls_language='".$langname."'";
                $usresult = $connect->Execute($usquery) or safe_die("Error deleting obsolete surveysettings<br />".$usquery."<br /><br /><strong>".$connect->ErrorMsg()); // Checked
                if ($usresult->RecordCount()==0)
                {

                    $bplang = new limesurvey_lang($langname);
                    $aDefaultTexts=aTemplateDefaultTexts($bplang,'unescaped');
                    if (getEmailFormat($surveyid) == "html")
                    {
                        $ishtml=true;
                        $aDefaultTexts['admin_detailed_notification']=$aDefaultTexts['admin_detailed_notification_css'].$aDefaultTexts['admin_detailed_notification'];
                    }
                    else
                    {
                        $ishtml=false;
                    }
                    $languagedetails=getLanguageDetails($langname);
                    $usquery = "INSERT INTO ".db_table_name('surveys_languagesettings')
                    ." (surveyls_survey_id, surveyls_language, surveyls_title, "
                    ." surveyls_email_invite_subj, surveyls_email_invite, "
                    ." surveyls_email_remind_subj, surveyls_email_remind, "
                    ." surveyls_email_confirm_subj, surveyls_email_confirm, "
                    ." surveyls_email_register_subj, surveyls_email_register, "
                    ." email_admin_notification_subj, email_admin_notification, "
                    ." email_admin_responses_subj, email_admin_responses, "
                    ." surveyls_dateformat) "
                    ." VALUES ({$postsid}, '".$langname."', '',"
                    .db_quoteall($aDefaultTexts['invitation_subject']).","
                    .db_quoteall($aDefaultTexts['invitation']).","
                    .db_quoteall($aDefaultTexts['reminder_subject']).","
                    .db_quoteall($aDefaultTexts['reminder']).","
                    .db_quoteall($aDefaultTexts['confirmation_subject']).","
                    .db_quoteall($aDefaultTexts['confirmation']).","
                    .db_quoteall($aDefaultTexts['registration_subject']).","
                    .db_quoteall($aDefaultTexts['registration']).","
                    .db_quoteall($aDefaultTexts['admin_notification_subject']).","
                    .db_quoteall($aDefaultTexts['admin_notification']).","
                    .db_quoteall($aDefaultTexts['admin_detailed_notification_subject']).","
                    .db_quoteall($aDefaultTexts['admin_detailed_notification']).","
                    .$languagedetails['dateformat'].")";
                    unset($bplang);
                    $usresult = $connect->Execute($usquery) or safe_die("Error deleting obsolete surveysettings<br />".$usquery."<br /><br />".$connect->ErrorMsg()); // Checked
                }
            }
        }



        if ($usresult)
        {
            $surveyselect = getsurveylist();
            $_SESSION['flashmessage'] = $clang->gT("Survey settings were successfully saved.");

        }
        else
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Survey could not be updated","js")."\n".$connect->ErrorMsg() ." ($usquery)\")\n //-->\n</script>\n";
        }
    }

    // Save the updated email settings
    elseif ($action == "updateemailtemplates" && bHasSurveyPermission($surveyid, 'surveylocale','update'))
    {
        $_POST  = array_map('db_quote', $_POST);
        $languagelist = GetAdditionalLanguagesFromSurveyID($surveyid);
        $languagelist[]=GetBaseLanguageFromSurveyID($surveyid);
        foreach ($languagelist as $langname)
        {
            if ($langname)
            {
                $usquery = "UPDATE ".db_table_name('surveys_languagesettings')." \n"
                . "SET surveyls_email_invite_subj='".$_POST['email_invite_subj_'.$langname]."', surveyls_email_invite='".$_POST['email_invite_'.$langname]."',"
                . "surveyls_email_remind_subj='".$_POST['email_remind_subj_'.$langname]."', surveyls_email_remind='".$_POST['email_remind_'.$langname]."',"
                . "surveyls_email_register_subj='".$_POST['email_register_subj_'.$langname]."', surveyls_email_register='".$_POST['email_register_'.$langname]."',"
                . "surveyls_email_confirm_subj='".$_POST['email_confirm_subj_'.$langname]."', surveyls_email_confirm='".$_POST['email_confirm_'.$langname]."',"
                . "email_admin_notification_subj='".$_POST['email_admin_notification_subj_'.$langname]."', email_admin_notification='".$_POST['email_admin_notification_'.$langname]."',"
                . "email_admin_responses_subj='".$_POST['email_admin_responses_subj_'.$langname]."', email_admin_responses='".$_POST['email_admin_responses_'.$langname]."' "
                . "WHERE surveyls_survey_id=".$surveyid." and surveyls_language='".$langname."'";
                $usresult = $connect->Execute($usquery) or safe_die("Error updating<br />".$usquery."<br /><br />".$connect->ErrorMsg());
            }
        }
        $_SESSION['flashmessage'] = $clang->gT("Email templates successfully saved.");
    }

    elseif ($action == "delsurvey" && bHasSurveyPermission($surveyid,'survey','delete')) //can only happen if there are no groups, no questions, no answers etc.
    {
        $query = "DELETE FROM {$dbprefix}surveys WHERE sid=$surveyid";
        $result = $connect->Execute($query);  // Checked
        if ($result)
        {
            $surveyid = "";
            $surveyselect = getsurveylist();
        }
        else
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("ERROR deleting Survey id","js")." ($surveyid)!\n$error\")\n //-->\n</script>\n";

        }
    }


    // Save the 2nd page from the survey-properties
    elseif (($action == "updatesurveylocalesettings") && bHasSurveyPermission($surveyid,'surveylocale','update'))
    {
        $languagelist = GetAdditionalLanguagesFromSurveyID($surveyid);
        $languagelist[]=GetBaseLanguageFromSurveyID($surveyid);
        require_once("../classes/inputfilter/class.inputfilter_clean.php");
        $myFilter = new InputFilter('','',1,1,1);

        foreach ($languagelist as $langname)
        {
            if ($langname)
            {

                if ($_POST['url_'.$langname] == 'http://') {$_POST['url_'.$langname]="";}

                // Clean XSS attacks
                if ($filterxsshtml)
                {
                    $_POST['short_title_'.$langname]=$myFilter->process($_POST['short_title_'.$langname]);
                    $_POST['description_'.$langname]=$myFilter->process($_POST['description_'.$langname]);
                    $_POST['welcome_'.$langname]=$myFilter->process($_POST['welcome_'.$langname]);
                    $_POST['endtext_'.$langname]=$myFilter->process($_POST['endtext_'.$langname]);
                    $_POST['urldescrip_'.$langname]=$myFilter->process($_POST['urldescrip_'.$langname]);
                    $_POST['url_'.$langname]=$myFilter->process($_POST['url_'.$langname]);
                }
                else
                {
                    $_POST['short_title_'.$langname] = html_entity_decode($_POST['short_title_'.$langname], ENT_QUOTES, "UTF-8");
                    $_POST['description_'.$langname] = html_entity_decode($_POST['description_'.$langname], ENT_QUOTES, "UTF-8");
                    $_POST['welcome_'.$langname] = html_entity_decode($_POST['welcome_'.$langname], ENT_QUOTES, "UTF-8");
                    $_POST['endtext_'.$langname] = html_entity_decode($_POST['endtext_'.$langname], ENT_QUOTES, "UTF-8");
                    $_POST['urldescrip_'.$langname] = html_entity_decode($_POST['urldescrip_'.$langname], ENT_QUOTES, "UTF-8");
                    $_POST['url_'.$langname] = html_entity_decode($_POST['url_'.$langname], ENT_QUOTES, "UTF-8");
                }

                // Fix bug with FCKEditor saving strange BR types
                $_POST['short_title_'.$langname]=fix_FCKeditor_text($_POST['short_title_'.$langname]);
                $_POST['description_'.$langname]=fix_FCKeditor_text($_POST['description_'.$langname]);
                $_POST['welcome_'.$langname]=fix_FCKeditor_text($_POST['welcome_'.$langname]);
                $_POST['endtext_'.$langname]=fix_FCKeditor_text($_POST['endtext_'.$langname]);

                $usquery = "UPDATE ".db_table_name('surveys_languagesettings')." \n"
                . "SET surveyls_title='".db_quote($_POST['short_title_'.$langname])."', surveyls_description='".db_quote($_POST['description_'.$langname])."',\n"
                . "surveyls_welcometext='".db_quote($_POST['welcome_'.$langname])."',\n"
                . "surveyls_endtext='".db_quote($_POST['endtext_'.$langname])."',\n"
                . "surveyls_url='".db_quote($_POST['url_'.$langname])."',\n"
                . "surveyls_urldescription='".db_quote($_POST['urldescrip_'.$langname])."',\n"
                . "surveyls_dateformat='".db_quote($_POST['dateformat_'.$langname])."',\n"
                . "surveyls_numberformat='".db_quote($_POST['numberformat_'.$langname])."'\n"
                . "WHERE surveyls_survey_id=".$postsid." and surveyls_language='".$langname."'";
                $usresult = $connect->Execute($usquery) or safe_die("Error updating<br />".$usquery."<br /><br /><strong>".$connect->ErrorMsg());   // Checked
            }
        }
        $_SESSION['flashmessage'] = $clang->gT("Survey text elements successfully saved.");
    }

}




elseif ($action == "insertsurvey" && $_SESSION['USER_RIGHT_CREATE_SURVEY'])
{
    $dateformatdetails=getDateFormatData($_SESSION['dateformat']);
    // $_POST['language']

    $supportedLanguages = getLanguageData();
    $numberformatid = $supportedLanguages[$_POST['language']]['radixpoint'];

    if ($_POST['url'] == 'http://') {$_POST['url']="";}
    if (!$_POST['surveyls_title'])
    {
        $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Survey could not be created because it did not have a title","js")."\")\n //-->\n</script>\n";
    } else
    {
        // Get random ids until one is found that is not used
        do
        {
            $surveyid = sRandomChars(5,'123456789');
            $isquery = "SELECT sid FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
            $isresult = db_execute_assoc($isquery); // Checked
        }
        while ($isresult->RecordCount()>0);

        if (!isset($_POST['template'])) {$_POST['template']='default';}
        if($_SESSION['USER_RIGHT_SUPERADMIN'] != 1 && $_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] != 1 && !hasTemplateManageRights($_SESSION['loginID'], $_POST['template'])) $_POST['template'] = "default";

        // insert base language into surveys_language_settings
        if ($filterxsshtml)
        {
            require_once("../classes/inputfilter/class.inputfilter_clean.php");
            $myFilter = new InputFilter('','',1,1,1);

            $_POST['surveyls_title']=$myFilter->process($_POST['surveyls_title']);
            $_POST['description']=$myFilter->process($_POST['description']);
            $_POST['welcome']=$myFilter->process($_POST['welcome']);
            $_POST['urldescrip']=$myFilter->process($_POST['urldescrip']);
        }
        else
        {
            $_POST['surveyls_title'] = html_entity_decode($_POST['surveyls_title'], ENT_QUOTES, "UTF-8");
            $_POST['description'] = html_entity_decode($_POST['description'], ENT_QUOTES, "UTF-8");
            $_POST['welcome'] = html_entity_decode($_POST['welcome'], ENT_QUOTES, "UTF-8");
            $_POST['urldescrip'] = html_entity_decode($_POST['urldescrip'], ENT_QUOTES, "UTF-8");
        }

        //make sure only numbers are passed within the $_POST variable
        $_POST['dateformat'] = (int) $_POST['dateformat'];
        $_POST['tokenlength'] = (int) $_POST['tokenlength'];


        if (trim($_POST['expires'])=='')
        {
            $_POST['expires']=null;
        }
        else
        {
            $datetimeobj = new Date_Time_Converter($_POST['expires'] , "d.m.Y H:i");
            $browsedatafield=$datetimeobj->convert("Y-m-d H:i:s");
            $_POST['expires']=$browsedatafield;
        }

        if (trim($_POST['startdate'])=='')
        {
            $_POST['startdate']=null;
        }
        else
        {
            $datetimeobj = new Date_Time_Converter($_POST['startdate'] , "d.m.Y H:i");
            $browsedatafield=$datetimeobj->convert("Y-m-d H:i:s");
            $_POST['startdate']=$browsedatafield;
        }


        $insertarray=array( 'sid'=>$surveyid,
                            'owner_id'=>$_SESSION['loginID'],
                            'admin'=>$_POST['admin'],
                            'active'=>'N',
                            'expires'=>$_POST['expires'],
                            'startdate'=>$_POST['startdate'],
                            'adminemail'=>$_POST['adminemail'],
                            'bounce_email'=>$_POST['bounce_email'],
                            'anonymized'=>$_POST['anonymized'],
                            'faxto'=>$_POST['faxto'],
                            'format'=>$_POST['format'],
                            'savetimings'=>$_POST['savetimings'],
                            'template'=>$_POST['template'],
                            'language'=>$_POST['language'],
                            'datestamp'=>$_POST['datestamp'],
                            'ipaddr'=>$_POST['ipaddr'],
                            'refurl'=>$_POST['refurl'],
                            'usecookie'=>$_POST['usecookie'],
                            'emailnotificationto'=>$_POST['emailnotificationto'],
                            'allowregister'=>$_POST['allowregister'],
                            'allowsave'=>$_POST['allowsave'],
                            'navigationdelay'=>$_POST['navigationdelay'],
                            'autoredirect'=>$_POST['autoredirect'],
                            'showxquestions'=>$_POST['showxquestions'],
                            'showgroupinfo'=>$_POST['showgroupinfo'],
                            'showqnumcode'=>$_POST['showqnumcode'],
                            'shownoanswer'=>$_POST['shownoanswer'],
                            'showwelcome'=>$_POST['showwelcome'],
                            'allowprev'=>$_POST['allowprev'],
                            'allowjumps'=>$_POST['allowjumps'],
                            'nokeyboard'=>$_POST['nokeyboard'],
                            'showprogress'=>$_POST['showprogress'],
                            'printanswers'=>$_POST['printanswers'],
        //                            'usetokens'=>$_POST['usetokens'],
                            'datecreated'=>date("Y-m-d"),
                            'listpublic'=>$_POST['public'],
                            'htmlemail'=>$_POST['htmlemail'],
                            'tokenanswerspersistence'=>$_POST['tokenanswerspersistence'],
                            'alloweditaftercompletion'=>$_POST['alloweditaftercompletion'],
                            'usecaptcha'=>$_POST['usecaptcha'],
                            'publicstatistics'=>$_POST['publicstatistics'],
                            'publicgraphs'=>$_POST['publicgraphs'],
                            'assessments'=>$_POST['assessments'],
                            'emailresponseto'=>$_POST['emailresponseto'],
                            'tokenlength'=>$_POST['tokenlength']
        );
        $dbtablename=db_table_name_nq('surveys');
        $isquery = $connect->GetInsertSQL($dbtablename, $insertarray);
        $isresult = $connect->Execute($isquery) or safe_die ($isquery."<br />".$connect->ErrorMsg()); // Checked



        // Fix bug with FCKEditor saving strange BR types
        $_POST['surveyls_title']=fix_FCKeditor_text($_POST['surveyls_title']);
        $_POST['description']=fix_FCKeditor_text($_POST['description']);
        $_POST['welcome']=fix_FCKeditor_text($_POST['welcome']);

        $bplang = new limesurvey_lang($_POST['language']);
        $aDefaultTexts=aTemplateDefaultTexts($bplang,'unescaped');
        $is_html_email = false;
        if (isset($_POST['htmlemail'])  && $_POST['htmlemail'] == "Y")
        {
            $is_html_email = true;
            $aDefaultTexts['admin_detailed_notification']=$aDefaultTexts['admin_detailed_notification_css'].conditional_nl2br($aDefaultTexts['admin_detailed_notification'],$is_html_email,'unescaped');
        }

        $insertarray=array( 'surveyls_survey_id'=>$surveyid,
                            'surveyls_language'=>$_POST['language'],
                            'surveyls_title'=>$_POST['surveyls_title'],
                            'surveyls_description'=>$_POST['description'],
                            'surveyls_welcometext'=>$_POST['welcome'],
                            'surveyls_urldescription'=>$_POST['urldescrip'],
                            'surveyls_endtext'=>$_POST['endtext'],
                            'surveyls_url'=>$_POST['url'],
                            'surveyls_email_invite_subj'=>$aDefaultTexts['invitation_subject'],
                            'surveyls_email_invite'=>conditional_nl2br($aDefaultTexts['invitation'],$is_html_email,'unescaped'),
                            'surveyls_email_remind_subj'=>$aDefaultTexts['reminder_subject'],
                            'surveyls_email_remind'=>conditional_nl2br($aDefaultTexts['reminder'],$is_html_email,'unescaped'),
                            'surveyls_email_confirm_subj'=>$aDefaultTexts['confirmation_subject'],
                            'surveyls_email_confirm'=>conditional_nl2br($aDefaultTexts['confirmation'],$is_html_email,'unescaped'),
                            'surveyls_email_register_subj'=>$aDefaultTexts['registration_subject'],
                            'surveyls_email_register'=>conditional_nl2br($aDefaultTexts['registration'],$is_html_email,'unescaped'),
                            'email_admin_notification_subj'=>$aDefaultTexts['admin_notification_subject'],
                            'email_admin_notification'=>conditional_nl2br($aDefaultTexts['admin_notification'],$is_html_email,'unescaped'),
                            'email_admin_responses_subj'=>$aDefaultTexts['admin_detailed_notification_subject'],
                            'email_admin_responses'=>$aDefaultTexts['admin_detailed_notification'],
                            'surveyls_dateformat'=>$_POST['dateformat'],
                            'surveyls_numberformat'=>$numberformatid
                          );
        $dbtablename=db_table_name_nq('surveys_languagesettings');
        $isquery = $connect->GetInsertSQL($dbtablename, $insertarray);
        $isresult = $connect->Execute($isquery) or safe_die ($isquery."<br />".$connect->ErrorMsg()); // Checked
        unset($bplang);

        $_SESSION['flashmessage'] = $clang->gT("Survey was successfully added.");

        // Update survey permissions
        GiveAllSurveyPermissions($_SESSION['loginID'],$surveyid);
        LimeExpressionManager::SetSurveyId($surveyid);

        $surveyselect = getsurveylist();

        // Create initial Survey table
        //include("surveytable_functions.php");
        //$creationResult = surveyCreateTable($surveyid);
        // Survey table could not be created
        //if ($creationResult !== true)
        //{
        //    safe_die ("Initial survey table could not be created, please report this as a bug."."<br />".$creationResult);
        //}
    }
}
elseif ($action == "savepersonalsettings")
{
    $_POST  = array_map('db_quote', $_POST);
    $uquery = "UPDATE {$dbprefix}users SET lang='{$_POST['lang']}', dateformat='{$_POST['dateformat']}', htmleditormode= '{$_POST['htmleditormode']}', questionselectormode= '{$_POST['questionselectormode']}', templateeditormode= '{$_POST['templateeditormode']}'
               WHERE uid={$_SESSION['loginID']}";
    $uresult = $connect->Execute($uquery)  or safe_die ($uquery."<br />".$connect->ErrorMsg());  // Checked
    $_SESSION['adminlang']=$_POST['lang'];
    $_SESSION['htmleditormode']=$_POST['htmleditormode'];
    $_SESSION['questionselectormode']=$_POST['questionselectormode'];
    $_SESSION['templateeditormode']=$_POST['templateeditormode'];
    $_SESSION['dateformat']= $_POST['dateformat'];
    $_SESSION['flashmessage'] = $clang->gT("Your personal settings were successfully saved.");
}
else
{
    include("access_denied.php");
}

/**
* This is a convenience function to update/delete answer default values. If the given
* $defaultvalue is empty then the entry is removed from table defaultvalues
*
* @param mixed $qid   Question ID
* @param mixed $scale_id  Scale ID
* @param mixed $specialtype  Special type (i.e. for  'Other')
* @param mixed $language     Language (defaults are language specific)
* @param mixed $defaultvalue    The default value itself
* @param boolean $ispost   If defaultvalue is from a $_POST set this to true to properly quote things
*/
function Updatedefaultvalues($qid,$sqid,$scale_id,$specialtype,$language,$defaultvalue,$ispost)
{
   global $connect;
   if ($defaultvalue=='')  // Remove the default value if it is empty
   {
      $connect->execute("DELETE FROM ".db_table_name('defaultvalues')." WHERE sqid=$sqid AND qid=$qid AND specialtype='$specialtype' AND scale_id={$scale_id} AND language='{$language}'");
   }
   else
   {
       $exists=$connect->GetOne("SELECT qid FROM ".db_table_name('defaultvalues')." WHERE sqid=$sqid AND qid=$qid AND specialtype=$specialtype'' AND scale_id={$scale_id} AND language='{$language}'");
       if ($exists===false || $exists===null)
       {
           $connect->execute('INSERT INTO '.db_table_name('defaultvalues')." (defaultvalue,qid,scale_id,language,specialtype,sqid) VALUES (".db_quoteall($defaultvalue,$ispost).",{$qid},{$scale_id},'{$language}','{$specialtype}',{$sqid})");
       }
       else
       {
           $connect->execute('UPDATE '.db_table_name('defaultvalues')." set defaultvalue=".db_quoteall($defaultvalue,$ispost)."  WHERE sqid={$sqid} AND qid={$qid} AND specialtype='{$specialtype}' AND scale_id={$scale_id} AND language='{$language}'");
       }
   }
}

?>
