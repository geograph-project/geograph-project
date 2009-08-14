<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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


#########################################

if (!$db) {
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
}

if (isset($_GET['getWorkerToken'])) {

	if ($wid = $db->getOne("SELECT at_home_worker_id FROM at_home_worker WHERE `ip` = INET_ATON('".getRemoteIP()."')")) { 
		die("Error:You already have a worker created for this IP address - please use that! If not created by you perhaps you are using a shared IP - which is NOT recommended for using this application");
	}
	

	$updates = array();
	if (!empty($_GET['team'])) {
		$updates['team'] = $_GET['team'];
	}
	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		die("Error:Please specify a useragent");
	}
	$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
	$db->Execute('INSERT INTO at_home_worker SET `ip` = INET_ATON(\''.getRemoteIP().'\'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
	$id = $db->Insert_ID();
	
	if (!$id) {
		die("Error:Unable to create a worker token");
	}
	$token=new Token;
	$token->setValue("id", $id);
	
	print "TOKEN: <TT>".$token->getToken()."</TT><BR>\n\n";
	print "<p>Put this in the script - its unique to you. If running multiple workers, get a token for each one!</p>";
	exit;
}

#########################################

if ($_GET['task'] != 'yahoo_terms') {
	//todo - add support for other tasks... 
	die('ERROR:invalid task');
}

if (!empty($_GET['worker'])) {
	$ok=false;
	$token=new Token;

	if ($token->parse($_GET['worker']) && $token->hasValue("id") && ($worker = intval($token->getValue("id"))) ) {
	} else {
		die('ERROR:invalid worker');
	}
} else {
	die('ERROR:specify worker');
}

#########################################

if (isset($_GET['getJob'])) {
	
	if ($jid = $db->getOne("SELECT at_home_job_id FROM at_home_job WHERE at_home_worker_id = $worker AND sent > DATE_SUB(NOW(),INTERVAL 24 HOUR)")) { 
		print "Success:{$jid}";
		exit;
	}
	
	//atomic claim! - looks messy, but avoids locks
	$pid = 999999+getmypid(); //something reasonably unique
	$db->Execute("UPDATE at_home_job SET at_home_worker_id = $pid WHERE sent = '0000-00-00 00:00:00' LIMIT 1");
	$row = $db->getRow("SELECT * FROM at_home_job WHERE at_home_worker_id = $pid");
	if (count($row)) {
		$db->Execute("UPDATE at_home_job SET at_home_worker_id = $worker,`sent`=NOW() WHERE at_home_job_id = {$row['at_home_job_id']} LIMIT 1");
	
		print "Success:{$row['at_home_job_id']}";
		
		exit;
	} else {
		die("Error:Unable to allocate job, maybe no outstanding jobs, otherwise try later...");
	}

} elseif (isset($_GET['downloadJobData'])) {
	$jid = intval($_GET['downloadJobData']);
	$row = $db->getRow("SELECT * FROM at_home_job WHERE at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00'");

	if (count($row)) {
		if ($max = $db->getOne("SELECT MAX(gridimage_id) FROM at_home_result WHERE gridimage_id BETWEEN {$row['start_gridimage_id']} AND {$row['end_gridimage_id']}") ) {
			$row['start_gridimage_id'] = $max+1;
		}
		$sql = "SELECT gridimage_id,title,comment,imageclass FROM gridimage_search WHERE gridimage_id BETWEEN {$row['start_gridimage_id']} AND {$row['end_gridimage_id']} AND LENGTH(comment) > 10 ORDER BY gridimage_id";
		
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$recordSet = &$db->Execute($sql);
		
		$f = fopen("php://output", "w");
		if (!$f) {
			die("ERROR:unable to open output stream");
		}
		while (!$recordSet->EOF) 
		{
			fputcsv($f,$recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
	
	} else {
		die("ERROR:unable to fetch data");
	}

} elseif (isset($_GET['submitJobResults'])) {
	$jid = intval($_GET['submitJobResults']);
	$row = $db->getRow("SELECT * FROM at_home_job WHERE at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00'");

	if (count($row)) {
		if (!count($_POST['results'])) {
			die("ERROR:nothing submitted?");
		}
		foreach ($_POST['results'] as $gid => $str) {
			if (!empty($str))
				foreach (explode('|',$str) as $result) {
					$updates = array();

					$updates['at_home_job_id'] = $jid;
					$updates['gridimage_id'] = intval($gid);
					$updates['result'] = $result; //the prepared query takes care of quoting

					$db->Execute('INSERT INTO at_home_result SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
				}
		}
		print "Success:$c saved";
	} else {
		die("ERROR:unable to identify job");
	}
	
} elseif (isset($_GET['finalizeJob'])) {
	$jid = intval($_GET['finalizeJob']);
	$row = $db->getRow("SELECT * FROM at_home_job WHERE at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00'");

	if (count($row)) {
		//todo - maybe we should validate?
		
		$db->Execute("UPDATE at_home_job SET `completed`=NOW() WHERE at_home_job_id = {$row['at_home_job_id']} LIMIT 1");
	
		print "Success:Thank you!";
	} else {
		die("ERROR:unable to identify job");
	}
	
} elseif (isset($_GET['createJobs'])) {
	init_session();
	$USER->mustHavePerm("admin");
	  
	  
	$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
	
	
	foreach (range(0,$max,5000) as $start) { 
	
		$updates = array();
		
		//$updates['task_id'] = 'yahoo_terms';
		$updates['start_gridimage_id'] = $start+1;
		$updates['end_gridimage_id'] = $start+5000;
		
		$updates['created'] = NULL;

		$db->Execute('INSERT INTO at_home_job SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print "crated {$updates['start_gridimage_id']} --&gt; {$updates['end_gridimage_id']}<br/>";
	}

} else {
	die("ERROR:specify action");
}

#########################################

