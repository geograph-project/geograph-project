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
init_session();

//you must be logged in to submit images
$USER->mustHavePerm("basic");


if (!empty($_POST) && empty($_GET['tab'])) {
	// HTTP headers for no cache etc
	header('Content-type: text/plain; charset=UTF-8');
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	// Settings
	$targetDir = $CONF['photo_upload_dir'];


	// Get parameters
	$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
	$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
	$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

	// Clean the fileName for security reasons
	$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

	// Look for the content type header
	if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

	if (isset($_SERVER["CONTENT_TYPE"]))
		$contentType = $_SERVER["CONTENT_TYPE"];

	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (strpos($contentType, "multipart") !== false) {
		if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
			// Open temp file
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
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
		$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
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

	if (!$uploadmanager->processUpload($targetDir.DIRECTORY_SEPARATOR.$fileName, true)) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "'.$uploadmanager->errormsg.'"}, "id" : "id"}');
	}

	// Return JSON-RPC response
	die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
}


$smarty = new GeographPage;

if (empty($_GET['tab'])) {
	
	$template = "submit_multi_upload.tpl";
	
} elseif ($_GET['tab'] == "upload") {
	
	$template = "submit_multi_upload.tpl";
	
} else {
	$template = "submit_multi_submit.tpl";
}
	

if ($template == "submit_multi_submit.tpl") {

	chdir($CONF['photo_upload_dir']);
	
	$files = glob("newpic_u{$USER->user_id}_*.exif");
	$data = array();
	
	foreach ($files as $file) {
		if (preg_match('/^newpic_u(\d)_(\w+).exif$/',$file,$m)) {
			if ($m[1] != $USER->user_id)
				continue;
			$data[] = array('transfer_id'=>$m[2],'uploaded'=>filemtime($file));
			//TODO check for tagged image!
		}
	}
	$smarty->assign_by_ref('data',$data);
}


$smarty->display($template);


