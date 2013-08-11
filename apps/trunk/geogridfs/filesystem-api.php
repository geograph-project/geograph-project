<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
	require_once '3rdparty/JSON.php';
	$json = new Services_JSON();


//todo, ideally read these from the filesyste, config.py file!
$DSN = 'mysql://user:pass@localhost/filesystem';

$file_table = 'file';
$folder_table = 'folder';

$db=NewADOConnection($DSN);

 header('Content-type: application/json');

if (!$db) {
	$data = array('error' => 'database offline');
	print $json->encode($data);
	exit;
}

if (empty($_GET['ident'])) {
	die('{"error": "please identify yourself!"}');
}

$row = $db->getRow("SELECT remote_id,secret FROM remote WHERE active = 1 AND identity = ".$db->Quote($_GET['ident']));
if (empty($row)) {
	die('{"error": "who are you again?"}');
}

$ident = $_GET['ident'];
$copy = $_GET;
unset($copy['sig']);
$raw = str_replace('%2F','/',http_build_query($copy)); ##alas python urllib.quote doesnt encode slashes
$sig = hash_hmac('md5',$raw,$row['secret']);

if ($sig != $_GET['sig'])
	die('{"error": "failure - please contact us"}');


if (empty($_GET['command'])) $_GET['command'] = 'log';
if ($_GET['command'] == 'filelist') {
	$where = '';
	$order = '';
	$limit = 100;

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = array();

	if (!empty($_GET['mode'])) {
		if ($_GET['mode'] == 'full') {
			//any files this identity doesnt have!
			$filter = "backup_target > 0";
			$order = " ORDER BY backup_count";
		} elseif ($_GET['mode'] == 'partial') {
			//get a share of as yet unreplicated files!
			$filter = "backup_count < backup_target";
		} else {
			die("unknown");
		}
		$where = "WHERE backups NOT LIKE '%$ident%' AND $filter"; //todo - change this to use bitmatchign!
		$limit = 1000;

		if (true) {
			$mode = $db->Quote($_GET['mode']);
			$task = $db->getRow("SELECT * FROM backup_task WHERE mode = $mode AND `identity` = '$ident' LIMIT 1");

			if (empty($task)) {
				//todo - need some sort of protection, rather than keep repeating this every time once there really no tasks!

				$db->Execute($sql = "INSERT INTO backup_task SELECT null,min(file_id) start,max(file_id) end,count(*) files,$mode as mode,'$ident' as identity
					FROM $file_table $where GROUP BY file_id DIV 1000 $order");

				$task = $db->getRow("SELECT * FROM backup_task WHERE mode = $mode `identity` = '$ident' LIMIT 1");
			}

			if (empty($task)) {
				$data['error'] = 'No tasks right now';
                        } else {
				$where .= " AND file_id BETWEEN {$task['start']} AND {$task['end']}";
				$data['task_id'] = $task['task_id'];
				$db->Execute("DELETE FROM backup_task WHERE task_id = {$task['task_id']}");
			}
		}

		//these are hardcoded, but included here SO they could be changed as required
		$data['docroot'] = '/geograph_live/public_html';
		$data['server'] = 'http://s0.geograph.org.uk';
		$data['sleep'] = 2;

	} elseif (!empty($_GET['folder'])) {

		if ($folder_id = $db->getOne("SELECT folder_id FROM $folder_table WHERE folder = ".$db->Quote($_GET['folder']))) {
			$where = "WHERE folder_id = ".$folder_id." AND backup_target > 0";
			$limit = 20000;
		} else {
			$data['error'] = 'Unknown folder';
		}
	} else {
		$data['error'] = 'Unknown mode';
	}

	if (!empty($where)) {
		//in theory should only offer such files for download, but just in case, could filter them out.
 		//$where .= " AND filename LIKE '{$data['docroot']}%'";

		$data['rows'] = $db->getAll("SELECT file_id,filename,backups,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified FROM $file_table $where $order LIMIT $limit");
	}

	customGZipHandlerStart();
	print $json->encode($data);


} elseif ($_GET['command'] == 'notify' && !empty($_POST['file_ids']) && preg_match('/^\d+( \d+)*$/',$_POST['file_ids'])) {

	$ids = explode(' ',$_POST['file_ids']);

	$sql = "UPDATE $file_table SET backups = CONCAT(backups,',$ident'), backup_count = backup_count+1 
			WHERE file_id IN (".implode(',',$ids).") AND backups NOT LIKE '%$ident%'"; //todo - change this to use bitmatchign!

	$db->Execute($sql);

	$data = array('ids_received' => count($ids), 'files_affected' => $db->Affected_Rows());
	print $json->encode($data);
}


if (!empty($row)) {

	$ins = "INSERT INTO api_log SET
	remote_id = {$row['remote_id']},
        command = ".$db->Quote(@$_GET['command']).",
        folder = ".$db->Quote(@$_GET['folder']).",
        `mode` = ".$db->Quote(@$_GET['mode']).",
        `notes` = ".$db->Quote(@$_POST['notes']).",
	ids = ".(empty($ids)?0:count($ids)).",
        ipaddr = INET_ATON('".getRemoteIP()."'),
        useragent = ".$db->Quote($_SERVER['HTTP_USER_AGENT']);

	$db->Execute($ins);
}
