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


$file_table = 'file';
$folder_table = 'folder';

$db=NewADOConnection($CONF['filesystem_dsn']);

 header('Content-type: application/json');

if (!$db) {
	$data = array('error' => 'database offline');
	print json_encode($data);
	exit;
}

if (empty($_GET['ident'])) {
	die('{"error": "please identify yourself!"}');
}

$row = $db->getRow("SELECT remote_id,secret,backup_included FROM remote WHERE active = 1 AND identity = ".$db->Quote($_GET['ident']));
if (empty($row)) {
	die('{"error": "who are you again?"}');
}
$ident = $_GET['ident'];


$copy = $_GET;
unset($copy['sig']);
$raw = str_replace('%2F','/',http_build_query($copy)); ##alas python urllib.quote doesnt encode slashes
$sig = hash_hmac('md5',$raw,$row['secret']);

if ($sig != $_GET['sig'] && empty($_GET['zauth']))
	die('{"error": "failure - please contact us"}');


if (empty($_GET['command'])) $_GET['command'] = 'log';
if ($_GET['command'] == 'filelist') {
	$where = '';
	$order = '';
	$limit = 100;

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = array();

	if (!empty($_GET['folder'])) {
		$_GET['folder'] = preg_replace('/\/$/','',$_GET['folder']);
		$_GET['folder'] = str_replace('\\','/',$_GET['folder']);
		if ($folder_id = $db->getOne("SELECT folder_id FROM $folder_table WHERE folder = ".$db->Quote($_GET['folder']))) {
			$where = "WHERE folder_id = ".$folder_id." AND backup_target > 0";
			$limit = 20000;
		} else {
			$data['error'] = 'Unknown folder';
		}

	} elseif (!empty($_GET['mode'])) {
                #if ($ident != 'dwo')
		#	 die('{"error": "Service Offline for Maintainace"}');

		$limit = 1000;
		if ($_GET['mode'] == 'recent') {
			$filter = "backup_count < backup_target";

			//recent, ALWAYS runs on live file table, so need to filter to avoid scanning the whole table!
			$count = $db->GetOne("SELECT MAX(file_id) FROM file");
			$filter .= " AND file_id > ".($count-80000);

			if ($ident == 'dwo')
				$filter .= " AND backup_count = 0"; //barry only downloads new files. intended as short term 'filler' rather than a long term backups

			$order = (rand(1,10) > 1)?" ORDER BY backup_count ASC,file_id DESC":" ORDER BY file_id DESC";
			$hour = intval(date('G'));
			$limit = ($hour < 8)?250:100;

		} elseif ($_GET['mode'] == 'full') {
			//any files this identity doesnt have!
			$filter = "backup_target > 0";
			$order = " ORDER BY backup_count";

		} elseif ($_GET['mode'] == 'backfill') {
			$filter = "backup_count < backup_target";
			if ($ident != 'dsp' && $ident != 'adc')
				$filter .= " AND backups not like '%uka%' AND backups not like '%ovh%'";
			$order = " ORDER BY file_id DESC";

		} elseif ($_GET['mode'] == 'partial') {
			//get a share of as yet unreplicated files!
			$filter = "backup_count < backup_target AND backups not like '%uka%' AND backups not like '%ovh%'";
			$order = " ORDER BY file_id DESC";

		} else {
			die('{"error": "mode not supported"}');
		}
		if (empty($row['backup_included']))
                        $filter .= " AND class != 'backup'";

		$where = "WHERE backups NOT LIKE '%$ident%' AND $filter AND replica_count > 0";

		if ($_GET['mode'] != 'recent') {
			$mode = $db->Quote($_GET['mode']);
			$hour = intval(date('G'));

			$taskorder = ($hour < 8)?"files DESC":(($hour > 12 && $hour < 18)?"RAND()":"task_id");
			$task = $db->getRow("SELECT * FROM backup_task WHERE mode = $mode AND `identity` = '$ident' ORDER BY $taskorder LIMIT 1");

			if (false && empty($task)) {
				//todo - need some sort of protection, rather than keep repeating this every time once there really are no tasks! (although with file_stat, it might not matter!)

				$filterQ = $db->Quote($filter);

				//this became ineffient, as it runs on the huge table, often with a full table scan
				//$db->Execute($sql = "INSERT INTO backup_task SELECT null,min(file_id) start,max(file_id) end,count(*) files,$mode as mode,'$ident' as identity, $filterQ AS clause, now() as created
				//	FROM $file_table $where GROUP BY file_id DIV 1000 $order");

				//more efficent to use the file_stat table, even if its slightly out of date!
				//note, we DONT use $order here, because tasks will get mixed anyway
				$db->Execute($sql = "INSERT INTO backup_task select null,min(shard)*10000 as start,max(shard)*10000+9999 as end,sum(count) as files,$mode as mode,'$ident' as identity, $filterQ AS clause, now() as created
					FROM file_stat $where GROUP BY shard ORDER BY NULL");

				$task = $db->getRow("SELECT * FROM backup_task WHERE mode = $mode AND `identity` = '$ident' LIMIT 1");
				$data['sql'] = $sql;
			}

			if (empty($task)) {
				$data['error'] = 'No tasks right now';
				$where .= " AND 0";
                        } else {
				if (!empty($task['clause']))
					$where = "WHERE backups NOT LIKE '%$ident%' AND ".$task['clause']." AND backup_target > 0 AND replica_count > 0";

				$where .= " AND file_id BETWEEN {$task['start']} AND {$task['end']}";
				$data['task_id'] = $task['task_id'];
				$db->Execute("DELETE FROM backup_task WHERE task_id = {$task['task_id']}");
			}
		}

		//these are hardcoded, but included here SO they could be changed as required
		$data['docroot'] = '/geograph_live/public_html';
		$data['server'] = 'http://s0.geograph.org.uk';
		if ($row['backup_included']) {
	                $data['backuproot'] = '/geograph_live/backups';
        	        $data['backupserver'] = 'http://backups.geograph.org.uk/folder-path';
		}
		$data['sleep'] = 1;

		$data['where'] = $where;
	} else {
		$data['error'] = 'Unknown mode';
	}

	if (!empty($where)) {
		$data['rows'] = $db->getAll("SELECT file_id,filename,backups,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified,replica_count FROM $file_table $where $order LIMIT $limit");
		//$data['mysql_error'] = mysql_error();

		$ids = array_keys($data['rows']); //just so have something to log - we only need to be able to do count($ids)
	}

	customGZipHandlerStart();
	print json_encode($data);


} elseif ($_GET['command'] == 'notify' && !empty($_POST['file_ids']) && preg_match('/^\d+( \d+)*$/',$_POST['file_ids'])) {

	$ids = explode(' ',$_POST['file_ids']);

	$sql = "UPDATE $file_table SET backups = CONCAT(backups,',$ident'), backup_count = backup_count+1
			WHERE file_id IN (".implode(',',$ids).") AND backups NOT LIKE '%$ident%'";
	$db->Execute($sql);

	$data = array('ids_received' => count($ids), 'files_affected' => $db->Affected_Rows());
	print json_encode($data);

} elseif ($_GET['command'] == 'drain' && !empty($_POST['file_ids']) && preg_match('/^\d+( \d+)*$/',$_POST['file_ids'])) {

	$ids = explode(' ',$_POST['file_ids']);

	$sql = "UPDATE $file_table SET backups = REPLACE(backups,'$ident',''), backup_count = backup_count-1
			WHERE file_id IN (".implode(',',$ids).") AND backups LIKE '%$ident%'";
	$db->Execute($sql);

	$data = array('ids_received' => count($ids), 'files_affected' => $db->Affected_Rows());
	print json_encode($data);
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
