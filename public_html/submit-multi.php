<?php
/**
 * $Project: GeoGraph $
 * $Id: contact.php 6600 2010-04-05 14:17:46Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');

if (empty($_POST) && empty($_GET)) { //specifially want to avoid ?redir=false, but actully GET used for other things, so avoid ALL
        $_GET['mobile'] = 0; //otherwise init_session will automatically redirect!
        $mobile_url = "https://{$_SERVER['HTTP_HOST']}/submit-multi.php?mobile=1"; //speciically https, as we may be http
}

init_session();

//you must be logged in to submit images
$USER->mustHavePerm("basic");

dieIfReadOnly();

pageMustBeHTTPS();

if (!empty($_POST) && !empty($_POST['urls'])) {
	print ' <meta name="viewport" content="width=device-width, initial-scale=1"> ';

	if (!empty($_POST['debug'])) {
		print "<pre>";
		print_r($_POST);
		print "</pre>";
	}
	$ids = array();
	if (preg_match_all('/https\:\/\/ucarecdn.com\/(\w[\w-]+)~(\d+)/',$_POST['urls'],$m)) {
		foreach ($m[1] as $idx => $uuid) {
			$num = $m[2][$idx];
			foreach (range(0,$num-1) as $i) {
				$url = "https://ucarecdn.com/{$uuid}~{$num}/nth/{$i}/.jpg"; //fake extension just to allow it via processURL

				if (!empty($_POST['debug']))
					print "<pre>$url</pre>";

		                print "<h3>".htmlentities("$uuid ".($i+1)."/$num")."</h3>";
		                $uploadmanager=new UploadManager;

		                if ($uploadmanager->processURL($url)) {
                		        print "<p>Copied successfully</p>";
					$ids[] = $uploadmanager->upload_id;
		                } else {
                		        print "<p>Error: {$uploadmanager->errormsg}</p>";
		                }
			}
		}

	} else foreach (explode("\n",str_replace("\r","",$_POST['urls'])) as $line) {
		list($filename,$url,$handle) = explode('|',$line);
		if (empty($url))
			continue;

		print "<h3>".htmlentities($filename)."</h3>";
	        $uploadmanager=new UploadManager;

	        if ($uploadmanager->processURL($url)) {
        	        print "<p>Copied successfully</p>";
			$ids[] = $uploadmanager->upload_id;
		} else {
        	        print "<p>Error: {$uploadmanager->errormsg}</p>";
        	}

		//todo, use $handle to REMOVE the fiule from storage!
	}

	$id = array_shift($ids);
	$url = "/submit2.php?transfer_id={$id}";

	if (!empty($_REQUEST['auto'])) {
		header("Location: $url");
		print "<script>window.location.href='$url';</script>";
		//use a script tag, because header might not work!
	}

	print " <a href=\"/submit-multi.php?tab=submit\">Continue with v1</a> ";
	print " <a href=\"/submit2.php?multi=true\">Continue with v2</a> (<a href=$url>Direct with FIRST image)</a>";

	if (!empty($_GET['mobile'])) {
		print "<p>Tip: Can also open these URLs on desktop browser, and continue the submission there.</p>";
		$url = "https://{$_SERVER['HTTP_HOST']}/submit-multi.php?tab=submit";
		print "<p>v1: <a href=$url>$url</a></p>";
		$url = "https://{$_SERVER['HTTP_HOST']}/submit2.php?multi=true";
		print "<p>v2: <a href=$url>$url</a></p>";
		print "or just goto the 'multi' submission method, the files are uploaded to general upload area";
	}

	exit;

} elseif (!empty($_POST) && !empty($_POST['name'])) {
	// HTTP headers for no cache etc
	header('Content-type: text/plain; charset=UTF-8');
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");


	// Get parameters
	$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
	$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
	#$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

	$fileName = tempnam("/tmp",'upload');

	// Look for the content type header
	if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

	if (isset($_SERVER["CONTENT_TYPE"]))
		$contentType = $_SERVER["CONTENT_TYPE"];

	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (strpos($contentType, "multipart") !== false) {
		if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
			// Open temp file
			$out = fopen($fileName, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen($_FILES['file']['tmp_name'], "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				fclose($in);
				fclose($out);
				@unlink($_FILES['file']['tmp_name']);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
	} else {
		// Open temp file
		$out = fopen($fileName, $chunk == 0 ? "wb" : "ab");
		if ($out) {
			// Read binary input stream and append it to temp file
			$in = fopen("php://input", "rb");

			if ($in) {
				while ($buff = fread($in, 4096))
					fwrite($out, $buff);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

			fclose($in);
			fclose($out);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
	}
	clearstatcache();
	
	$uploadmanager=new UploadManager;

	if (!$uploadmanager->processUpload($fileName, true)) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "'.$uploadmanager->errormsg.'"}, "id" : "id"}');
	}

	// Return JSON-RPC response
	die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
}


$smarty = new GeographPage;
$cacheid = 'normal';

if (!empty($CONF['submission_message'])) {
        $smarty->assign("status_message",$CONF['submission_message']);
}

if (isset($_SERVER['HTTP_X_PSS_LOOP']) && $_SERVER['HTTP_X_PSS_LOOP'] == 'pagespeed_proxy') {
	$smarty->assign("status_message",'<div class="interestBox" style="background-color:yellow;border:6px solid red;padding:20px;margin:20px;font-size:1.1em;">geograph.org.uk is currently in reduced functionality mode - to deal with traffic levels. <b>The maximum filesize that can be uploaded is now 5Mb.</b> To upload a larger image, please use <a href="https://www.geograph.ie/submit2.php">www.geograph.ie</a> or <a href="http://schools.geograph.org.uk/submit2.php" onclick="location.host = \'schools.geograph.org.uk\'; return false">schools.geograph.org.uk</a> <small>(they upload to the same database)</small></div>');
	$smarty->assign("small_upload",1);
}

if (!empty($_GET['mobile'])) {
	$smarty->assign("mobile",1);
	$smarty->assign("canonical",$CONF['SELF_HOST']."/".basename($_SERVER['PHP_SELF']));
	$cacheid = 'mobile';
}

if (empty($_GET['tab'])) {

	$template = "submit_multi_upload.tpl";
	$smarty->assign("page_title","Multi-Upload");

} elseif ($_GET['tab'] == "upload") {

	$template = "submit_multi_upload.tpl";
	$smarty->assign("page_title","Multi-Upload");

} elseif ($_GET['tab'] == "cloud") {

	$template = "submit_multi_cloud.tpl";

} elseif ($_GET['tab'] == "nofrills") {

	$template = "submit_multi_nofrills.tpl";

	$dirs = array (-1 => '');
	$jump = 360/16; $jump2 = 360/32;
	for($q = 0; $q< 360; $q+=$jump) {
		$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
		$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
			str_pad($s,16,chr(160)),
			$q,
			($q == 0?$q+360-$jump2:$q-$jump2),
			$q+$jump2);
	}
	$dirs['00'] = $dirs[0];
	$smarty->assign_by_ref('dirs', $dirs);

} else {
	$template = "submit_multi_submit.tpl";
	$uploadmanager=new UploadManager;

	if (!empty($_GET['delete']) && $uploadmanager->validUploadId($_GET['delete']) ) {

		$uploadmanager->setUploadId($_GET['delete'],false);

		$uploadmanager->cleanUp();
	}
}

if ($template == "submit_multi_submit.tpl" || $template == "submit_multi_nofrills.tpl") {

	$uploadmanager=new UploadManager;

	$data = $uploadmanager->getUploadedFiles();

	$smarty->assign_by_ref('data',$data);
} elseif (empty($CONF['submission_message'])) {
	customExpiresHeader(3600,false,true);
}


$smarty->display($template,$cacheid);


