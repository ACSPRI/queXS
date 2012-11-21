<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php
    if (count($_POST)==0 && !((isset($subaction) && $subaction == 'navigation_test'))) {die("Cannot run this script directly");}
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>LEM Navigation Test</title>
    </head>
    <body>
        <?php
            if (count($_POST) == 0) {
                $clang = new limesurvey_lang("en");

                $query = "select a.surveyls_survey_id as sid, a.surveyls_title as title, b.datecreated, b.assessments "
                . "from " . db_table_name('surveys_languagesettings') . " as a join ". db_table_name('surveys') . " as b on a.surveyls_survey_id = b.sid"
                . " where a.surveyls_language='en' order by a.surveyls_title, b.datecreated";
                $data = db_execute_assoc($query);
                $surveyList='';
                foreach($data->GetRows() as $row) {
                    $surveyList .= "<option value='" . $row['sid'] .'|' . $row['assessments'] . "'>#" . $row['sid'] . " [" . $row['datecreated'] . '] ' . FlattenText($row['title']) . "</option>\n";
                }

                $form = <<< EOD
<form method='post' action='../classes/expressions/test/navigation_test.php'>
<h3>Enter the following variables to test navigation for a survey using different styles</h3>
<table border='1'>
<tr><th>Parameter</th><th>Value</th></tr>
<tr><td>Survey ID (SID)</td>
<td><select name='sid' id='sid'>
$surveyList
</select></td></tr>
<tr><td>Navigation Style</td>
<td><select name='surveyMode' id='surveyMode'>
    <option value='question'>Question (One-at-a-time)</option>
    <option value='group' selected='selected'>Group (Group-at-a-time)</option>
    <option value='survey'>Survey (All-in-one)</option>
</select></td></tr>
<tr><td>Debug Log Level</td>
<td>
Specify which debugging features to use
<ul>
<li><input type='checkbox' name='LEM_DEBUG_TIMING' id='LEM_DEBUG_TIMING' value='Y'/>Detailed Timing</li>
<li><input type='checkbox' name='LEM_DEBUG_VALIDATION_SUMMARY' id='LEM_DEBUG_VALIDATION_SUMMARY' value='Y' checked="checked"/>Validation Summary</li>
<li><input type='checkbox' name='LEM_DEBUG_VALIDATION_DETAIL' id='LEM_DEBUG_VALIDATION_DETAIL' value='Y' checked="checked"/>Validation Detail (Validation Summary must also be checked to see detail)</li>
<li><input type='checkbox' name='LEM_PRETTY_PRINT_ALL_SYNTAX' id='LEM_PRETTY_PRINT_ALL_SYNTAX' value='Y' checked="checked"/>Pretty Print Syntax</li>
<li><input type='checkbox' name='deletenonvalues' id='deletenonvalues' value='Y' checked="checked"/>Delete Non-Values</li>
</ul></td>
</tr>
<tr><td colspan='2'><input type='submit'/></td></tr>
</table>
</form>
EOD;
                echo $form;
            }
            else {
                include_once('../LimeExpressionManager.php');
                require_once('../../../classes/core/startup.php');
                require_once('../../../config-defaults.php');
                require_once('../../../common.php');
                require_once('../../../classes/core/language.php');

                $clang = new limesurvey_lang("en");

                $surveyInfo = explode('|',$_POST['sid']);
                $surveyid = $surveyInfo[0];
                $assessments = ($surveyInfo[1] == 'Y');
                $surveyMode = $_POST['surveyMode'];
                $LEMdebugLevel = (
                ((isset($_POST['LEM_DEBUG_TIMING']) && $_POST['LEM_DEBUG_TIMING'] == 'Y') ? LEM_DEBUG_TIMING : 0) +
                ((isset($_POST['LEM_DEBUG_VALIDATION_SUMMARY']) && $_POST['LEM_DEBUG_VALIDATION_SUMMARY'] == 'Y') ? LEM_DEBUG_VALIDATION_SUMMARY : 0) +
                ((isset($_POST['LEM_DEBUG_VALIDATION_DETAIL']) && $_POST['LEM_DEBUG_VALIDATION_DETAIL'] == 'Y') ? LEM_DEBUG_VALIDATION_DETAIL : 0) +
                ((isset($_POST['LEM_PRETTY_PRINT_ALL_SYNTAX']) && $_POST['LEM_PRETTY_PRINT_ALL_SYNTAX'] == 'Y') ? LEM_PRETTY_PRINT_ALL_SYNTAX : 0)
                );
                $deletenonvalues = ((isset($_POST['deletenonvalues']) && $_POST['deletenonvalues']=='Y') ? 1 : 0);

                $surveyOptions = array(
                'active'=>false,
                'allowsave'=>true,
                'anonymized'=>false,
                'assessments'=>$assessments,
                'datestamp'=>true,
                'deletenonvalues'=>$deletenonvalues,
                'hyperlinkSyntaxHighlighting'=>true,
                'ipaddr'=>true,
                'rooturl'=>'../../..',
                );

                print '<h3>Starting survey ' . $surveyid . " using Survey Mode '". $surveyMode . (($assessments) ? "' [Uses Assessments]" : "'") . "</h3>";
                $now = microtime(true);
                LimeExpressionManager::StartSurvey($surveyid, $surveyMode, $surveyOptions, true,$LEMdebugLevel);
                print '<b>[StartSurvey() took ' . (microtime(true) - $now) . ' seconds]</b><br/>';

                while(true) {
                    $now = microtime(true);
                    $result = LimeExpressionManager::NavigateForwards(true);
                    print $result['message'] . "<br/>";
                    LimeExpressionManager::FinishProcessingPage();
                    //                    print LimeExpressionManager::GetRelevanceAndTailoringJavaScript();
                    if (($LEMdebugLevel & LEM_DEBUG_TIMING) == LEM_DEBUG_TIMING) {
                        print LimeExpressionManager::GetDebugTimingMessage();
                    }
                    print '<b>[NavigateForwards() took ' . (microtime(true) - $now) . ' seconds]</b><br/>';
                    if (is_null($result) || $result['finished'] == true) {
                        break;
                    }
                }
                print "<h3>Finished survey " . $surveyid . "</h3>";
            }
        ?>
    </body>
</html>
