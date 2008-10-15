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
* $Id: import_resources_zip.php 3685 2007-11-22 04:53:18Z jcleeland $
*/


include_once("login_check.php");

if (!isset($surveyid))
{
	returnglobal('sid');
}

if (!isset($lid))
{
	returnglobal('lid');
}

if ($action == "importsurvresources" && $surveyid) {
	require("classes/phpzip/phpzip.inc.php");
	$zipfile=$_FILES['the_file']['tmp_name'];
	$z = new PHPZip();

	// Create temporary directory
	// If dangerous content is unzipped
	// then no one will know the path
	$extractdir=tempdir($tempdir);
	$basedestdir = $publicdir."/upload/surveys";
	$destdir=$basedestdir."/$surveyid/";

	$importsurvresourcesoutput = "<br />\n";
	$importsurvresourcesoutput .= "<table class='alertbox'>\n";
	$importsurvresourcesoutput .= "\t<tr><td colspan='2' height='4'><strong>".$clang->gT("Import Survey Resources")."</strong></td></tr>\n";
	$importsurvresourcesoutput .= "\t<tr><td align='center'>\n";

	if (!is_writeable($basedestdir))
	{
		$importsurvresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
	    $importsurvresourcesoutput .= sprintf ($clang->gT("Incorrect permissions in your %s folder."),$basedestdir)."<br /><br />\n";
		$importsurvresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=editsurvey&sid=$surveyid', '_top')\">\n";
		$importsurvresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
		return;
	}

	if (!is_dir($destdir))
	{
		mkdir($destdir);
	}

	$aImportedFilesInfo=null;
	$aErrorFilesInfo=null;


	if (is_file($zipfile))
	{
		$importsurvresourcesoutput .= "<strong><font class='successtitle'>".$clang->gT("Success")."</font></strong><br />\n";
		$importsurvresourcesoutput .= $clang->gT("File upload succeeded.")."<br /><br />\n";
		$importsurvresourcesoutput .= $clang->gT("Reading file..")."<br />\n";

		if ($z->extract($extractdir,$zipfile) != 'OK')
		{
			$importsurvresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
			$importsurvresourcesoutput .= $clang->gT("This file is not a valid ZIP file archive. Import failed.")."<br /><br />\n";
			$importsurvresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=editsurvey&sid=$surveyid', '_top')\">\n";
			$importsurvresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
			return;
		}

		// now read tempdir and copy authorized files only
		$dh = opendir($extractdir);
		while($direntry = readdir($dh))
		{
			if (($direntry!=".")&&($direntry!=".."))
			{
				if (is_file($extractdir."/".$direntry))
				{ // is  a file
					$extfile = substr(strrchr($direntry, '.'),1);
					if  (!(stripos(','.$allowedresourcesuploads.',',','.$extfile.',') === false))
					{ //Extension allowed
						if (!copy($extractdir."/".$direntry, $destdir.$direntry))
						{
							$aErrorFilesInfo[]=Array(
								"filename" => $direntry,
								"status" => $clang->gT("Copy failed")
							);
							unlink($extractdir."/".$direntry);
							
						}
						else
						{	
							$aImportedFilesInfo[]=Array(
								"filename" => $direntry,
								"status" => $clang->gT("OK")
							);
							unlink($extractdir."/".$direntry);
						}
					}
					
					else
					{ // Extension forbidden
						$aErrorFilesInfo[]=Array(
							"filename" => $direntry,
							"status" => $clang->gT("Error")." (".$clang->gT("Forbidden Extension").")"
						);
						unlink($extractdir."/".$direntry);
					}
				} // end if is_file
			} // end if ! . or ..
		} // end while read dir
		

		//Delete the temporary file
		unlink($zipfile);
		//Delete temporary folder
		rmdir($extractdir);

		// display summary
		$okfiles = 0;
		$errfiles= 0;
	        $ErrorListHeader .= "";
	        $ImportListHeader .= "";
		if (is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
		{
			$status=$clang->gT("Success");
			$color='green';
			$okfiles = count($aImportedFilesInfo);
		        $ImportListHeader .= "<br /><strong><u>".$clang->gT("Imported Files List").":</u></strong><br />\n";
		}
		elseif (is_null($aErrorFilesInfo) && is_null($aImportedFilesInfo))
		{
			$importsurvresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
			$importsurvresourcesoutput .= $clang->gT("This ZIP archive contains no valid Resources files. Import failed.")."<br /><br />\n";
			$importsurvresourcesoutput .= $clang->gT("Remember that we do not support subdirectories in ZIP Archive.")."<br /><br />\n";
			$importsurvresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=editsurvey&sid=$surveyid', '_top')\">\n";
			$importsurvresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
			return;
			
		}
		elseif (!is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
		{
			$status=$clang->gT("Partial");
			$color='orange';
			$okfiles = count($aImportedFilesInfo);
			$errfiles = count($aErrorFilesInfo);
		        $ErrorListHeader .= "<br /><strong><u>".$clang->gT("Error Files List").":</u></strong><br />\n";
		        $ImportListHeader .= "<br /><strong><u>".$clang->gT("Imported Files List").":</u></strong><br />\n";
		}
		else
		{
			$status=$clang->gT("Error");
			$color='red';
			$errfiles = count($aErrorFilesInfo);
		        $ErrorListHeader .= "<br /><strong><u>".$clang->gT("Error Files List").":</u></strong><br />\n";
		}

        		$importsurvresourcesoutput .= "<strong>".$clang->gT("Imported Resources for")." SID:</strong> $surveyid<br />\n";
		        $importsurvresourcesoutput .= "<br />\n<strong><font color='$color'>".$status."</font></strong><br />\n";
		        $importsurvresourcesoutput .= "<strong><u>".$clang->gT("Resources Import Summary")."</u></strong><br />\n";
		        $importsurvresourcesoutput .= "".$clang->gT("Total Imported files").": $okfiles<br />\n";
		        $importsurvresourcesoutput .= "".$clang->gT("Total Errors").": $errfiles<br />\n";
			$importsurvresourcesoutput .= $ImportListHeader;
			foreach ($aImportedFilesInfo as $entry)
			{
		        	$importsurvresourcesoutput .= "\t<li>".$clang->gT("File").": ".$entry["filename"]."</li>\n";
			}
		        $importsurvresourcesoutput .= "\t</ul><br /><br />\n";
			$importsurvresourcesoutput .= $ErrorListHeader;
			foreach ($aErrorFilesInfo as $entry)
			{
		        	$importsurvresourcesoutput .= "\t<li>".$clang->gT("File").": ".$entry['filename']." (".$entry['status'].")</li>\n";
			}
	}
	else
	{
		$importsurvresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
	    $importsurvresourcesoutput .= sprintf ($clang->gT("An error occurred uploading your file. This may be caused by incorrect permissions in your %s folder."),$basedestdir)."<br /><br />\n";
		$importsurvresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=editsurvey&sid=$surveyid', '_top')\">\n";
		$importsurvresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
		return;
	}
		// Final Back not needed if files have been imported
//		$importsurvresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=editsurvey&sid=$surveyid', '_top')\">\n";
		$importsurvresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
}



if ($action == "importlabelresources" && $lid)
{
	require("classes/phpzip/phpzip.inc.php");
	//$the_full_file_path = $tempdir . "/" . $_FILES['the_file']['name'];
	$zipfile=$_FILES['the_file']['tmp_name'];
	$z = new PHPZip();
	// Create temporary directory
	// If dangerous content is unzipped
	// then no one will know the path
	$extractdir=tempdir($tempdir);
	$basedestdir = $publicdir."/upload/labels";
	$destdir=$basedestdir."/$lid/";

	$importlabelresourcesoutput = "<br />\n";
	$importlabelresourcesoutput .= "<table class='alertbox'>\n";
	$importlabelresourcesoutput .= "\t<tr><td colspan='2' height='4'><strong>".$clang->gT("Import Label Set")."</strong></td></tr>\n";
	$importlabelresourcesoutput .= "\t<tr><td align='center'>\n";

	if (!is_writeable($basedestdir))
	{
		$importlabelresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
	    $importlabelresourcesoutput .= sprintf ($clang->gT("Incorrect permissions in your %s folder."),$basedestdir)."<br /><br />\n";
		$importlabelresourcesoutput .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?action=labels&lid=$lid', '_top')\">\n";
		$importlabelresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
		return;
	}

	if (!is_dir($destdir))
	{
		mkdir($destdir);
	}

	$aImportedFilesInfo=null;
	$aErrorFilesInfo=null;


	if (is_file($zipfile))
	{
		$importlabelresourcesoutput .= "<strong><font class='successtitle'>".$clang->gT("Success")."</font></strong><br />\n";
		$importlabelresourcesoutput .= $clang->gT("File upload succeeded.")."<br /><br />\n";
		$importlabelresourcesoutput .= $clang->gT("Reading file..")."<br />\n";

		if ($z->extract($extractdir,$zipfile) != 'OK')
		{
			$importlabelresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
			$importlabelresourcesoutput .= $clang->gT("This file is not a valid ZIP file archive. Import failed.")."<br /><br />\n";
			$importlabelresourcesoutput .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?action=labels&lid=$lid', '_top')\">\n";
			$importlabelresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
			return;
		}

		// now read tempdir and copy authorized files only
		$dh = opendir($extractdir);
		while($direntry = readdir($dh))
		{
			if (($direntry!=".")&&($direntry!=".."))
			{
				if (is_file($extractdir."/".$direntry))
				{ // is  a file
					$extfile = substr(strrchr($direntry, '.'),1);
					if  (!(stripos(','.$allowedresourcesuploads.',',','.$extfile.',') === false))
					{ //Extension allowed
						if (!copy($extractdir."/".$direntry, $destdir.$direntry))
						{
							$aErrorFilesInfo[]=Array(
								"filename" => $direntry,
								"status" => $clang->gT("Copy failed")
							);
							unlink($extractdir."/".$direntry);
							
						}
						else
						{	
							$aImportedFilesInfo[]=Array(
								"filename" => $direntry,
								"status" => $clang->gT("OK")
							);
							unlink($extractdir."/".$direntry);
						}
					}
					
					else
					{ // Extension forbidden
						$aErrorFilesInfo[]=Array(
							"filename" => $direntry,
							"status" => $clang->gT("Error")." (".$clang->gT("Forbidden Extension").")"
						);
						unlink($extractdir."/".$direntry);
					}
				} // end if is_file
			} // end if ! . or ..
		} // end while read dir
		

		//Delete the temporary file
		unlink($zipfile);
		//Delete temporary folder
		rmdir($extractdir);

		// display summary
		$okfiles = 0;
		$errfiles= 0;
	        $ErrorListHeader .= "";
	        $ImportListHeader .= "";
		if (is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
		{
			$status=$clang->gT("Success");
			$color='green';
			$okfiles = count($aImportedFilesInfo);
		        $ImportListHeader .= "<br /><strong><u>".$clang->gT("Imported Files List").":</u></strong><br />\n";
		}
		elseif (is_null($aErrorFilesInfo) && is_null($aImportedFilesInfo))
		{
			$importlabelresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
			$importlabelresourcesoutput .= $clang->gT("This ZIP archive contains no valid Resources files. Import failed.")."<br /><br />\n";
			$importlabelresourcesoutput .= $clang->gT("Remember that we do not support subdirectories in ZIP Archive.")."<br /><br />\n";
			$importlabelresourcesoutput .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?action=labels&lid=$lid', '_top')\">\n";
			$importlabelresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
			return;
			
		}
		elseif (!is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
		{
			$status=$clang->gT("Partial");
			$color='orange';
			$okfiles = count($aImportedFilesInfo);
			$errfiles = count($aErrorFilesInfo);
		        $ErrorListHeader .= "<br /><strong><u>".$clang->gT("Error Files List").":</u></strong><br />\n";
		        $ImportListHeader .= "<br /><strong><u>".$clang->gT("Imported Files List").":</u></strong><br />\n";
		}
		else
		{
			$status=$clang->gT("Error");
			$color='red';
			$errfiles = count($aErrorFilesInfo);
		        $ErrorListHeader .= "<br /><strong><u>".$clang->gT("Error Files List").":</u></strong><br />\n";
		}

        		$importlabelresourcesoutput .= "<strong>".$clang->gT("Imported Resources for")." LID:</strong> $lid<br />\n";
		        $importlabelresourcesoutput .= "<br />\n<strong><font color='$color'>".$status."</font></strong><br />\n";
		        $importlabelresourcesoutput .= "<strong><u>".$clang->gT("Resources Import Summary")."</u></strong><br />\n";
		        $importlabelresourcesoutput .= "".$clang->gT("Total Imported files").": $okfiles<br />\n";
		        $importlabelresourcesoutput .= "".$clang->gT("Total Errors").": $errfiles<br />\n";
			$importlabelresourcesoutput .= $ImportListHeader;
			foreach ($aImportedFilesInfo as $entry)
			{
		        	$importlabelresourcesoutput .= "\t<li>".$clang->gT("File").": ".$entry["filename"]."</li>\n";
			}
		        $importlabelresourcesoutput .= "\t</ul><br /><br />\n";
			$importlabelresourcesoutput .= $ErrorListHeader;
			foreach ($aErrorFilesInfo as $entry)
			{
		        	$importlabelresourcesoutput .= "\t<li>".$clang->gT("File").": ".$entry['filename']." (".$entry['status'].")</li>\n";
			}
	}
	else
	{
		$importlabelresourcesoutput .= "<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n";
	    $importlabelresourcesoutput .= sprintf ($clang->gT("An error occurred uploading your file. This may be caused by incorrect permissions in your %s folder."),$basedestdir)."<br /><br />\n";
		$importlabelresourcesoutput .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?action=labels&lid=$lid', '_top')\">\n";
		$importlabelresourcesoutput .= "</td></tr></table><br />&nbsp;\n";
		return;
	}
			$importlabelresourcesoutput .= "<input type='submit' value='".$clang->gT("Back")."' onclick=\"window.open('$scriptname?action=labels&lid=$lid', '_top')\">\n";
}



//---------------------
	// Comes from http://fr2.php.net/tempnam
 function tempdir($dir, $prefix='', $mode=0700)
  {
    if (substr($dir, -1) != '/') $dir .= '/';

    do
    {
      $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (!mkdir($path, $mode));

    return $path;
  }

?>
