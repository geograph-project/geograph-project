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

	if ($wid = $db->getOne("SELECT at_home_worker_id FROM at_home_worker WHERE `ip` = INET_ATON('".mysql_real_escape_string(getRemoteIP())."')")) { 
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
	$db->Execute('INSERT INTO at_home_worker SET `ip` = INET_ATON(\''.mysql_real_escape_string(getRemoteIP()).'\'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
	$id = $db->Insert_ID();
	
	if (!$id) {
		die("Error:Unable to create a worker token");
	}
	$token=new Token;
	$token->setValue("id", $id);
	
	if (isset($_GET['output']) && $_GET['output']=='text') {
		setcookie('workerToken', $token->getToken(), time()+3600*24*365,'/');
		print "Thank You. You may now begin processing jobs.";
		
	} else {
		print "TOKEN: <TT>".$token->getToken()."</TT><BR>\n\n";
		print "<p>Put this in the script - its unique to you. If running multiple workers, get a token for each one!</p>";
	}
	exit;
}

#########################################

$task = $_GET['task'];
if (!in_array($task,array('yahoo_terms','carrot2'))) {
	die('ERROR:invalid task');
}

if (!empty($_GET['worker'])) {
	
	if ($_GET['worker'] == 'cookie') {
		$_GET['worker'] = $_COOKIE['workerToken'];
	}

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
#########################################

if (isset($_GET['getJob'])) {
	
                //check load average, abort if too high
                $buffer = "0 0 0";
                if (is_readable("/proc/loadavg")) {
                        $f = fopen("/proc/loadavg","r");
                        if ($f)
                        {
                                if (!feof($f)) {
                                        $buffer = fgets($f, 1024);
                                }
                                fclose($f);
                        }
                }
                $loads = explode(" ",$buffer);
                $load=(float)$loads[0];
		if ($load>0.6) {
			if (isset($_GET['output']) && $_GET['output']=='json') {
				die("{error: 'Server Busy, try later'}");
                	} else {
        	                die("Error:Server Busy, try later");
	                }

		}

	//yahoo should only ever have one worker!
	$timeout = ($task == 'yahoo_terms')?'1 MINUTE':"10 MINUTE";

	//find any jobs not completed - so can be resumed
	if ($jid = $db->getOne("SELECT at_home_job_id FROM at_home_job WHERE `task` = '$task' AND at_home_worker_id = $worker AND completed = '0000-00-00 00:00:00' AND sent < DATE_SUB(NOW(),INTERVAL $timeout) ")) { 
		if (isset($_GET['output']) && $_GET['output']=='json') {
			print "{jobId: $jid}";
		} else {
			print "Success:{$jid}";
		}
		if (isset($_GET['downloadJobData'])) {
			$_GET['downloadJobData'] = $jid;
			print ":";
		} else {
			exit;
		}
	} else {

		if ($task == 'yahoo_terms') {
			if (strpos($_SERVER['HTTP_USER_AGENT'],"Geograph-At-Home") !== 0) {
				if (isset($_GET['output']) && $_GET['output']=='json') {
					die("{error: 'There are no jobs left for the Javascript client to work on!'}");
				} else {
					die("Error:There are no jobs left for the Javascript client to work on!");
				}
			}

			if ($worker == 8) {
				$hours = floor(24/10);
			} elseif ($worker == 11) {
				$hours = ceil(24/5);
			} else {
				$hours = 24;
			}

			//If there is a recent job die - dont want it too often. (but a part completed job is caught above) 	
			if (empty($_GET['force']) && ($jid = $db->getOne("SELECT at_home_job_id FROM at_home_job WHERE at_home_worker_id = $worker AND sent > DATE_ADD(DATE_SUB(NOW(),INTERVAL $hours HOUR),INTERVAL 10 MINUTE)"))) { 
				if (isset($_GET['output']) && $_GET['output']=='json') {
					die("{error: 'You already have a job allocated (id:$jid) in the last $hours hours - we only want one job per worker per $hours hours'}");
				} else {
					die("Error:You already have a job allocated (id:$jid) in the last $hours hours - we only want one job per worker per $hours hours");
				}
			}
		}

		//atomic claim! - looks messy, but avoids locks
		$pid = 99999+(getmypid()+$worker); //something reasonably unique
		$db->Execute("UPDATE at_home_job SET at_home_worker_id = $pid WHERE `task` = '$task' AND sent = '0000-00-00 00:00:00' LIMIT 1");
		$row = $db->getRow("SELECT * FROM at_home_job WHERE at_home_worker_id = $pid");
		if (count($row)) {
			$jid = $row['at_home_job_id'];
			$db->Execute("UPDATE at_home_job SET at_home_worker_id = $worker,`sent`=NOW() WHERE at_home_job_id = $jid LIMIT 1");

			if (isset($_GET['output']) && $_GET['output']=='json') {
				setcookie('workerActive', date('r'), time()+600,'/');
				print "{jobId: $jid}";
			} else {
				print "Success:$jid";
			}
			if (isset($_GET['downloadJobData'])) {
				$_GET['downloadJobData'] = $jid;
				print ":";
			} else {
				exit;
			}
		} else {
			if (isset($_GET['output']) && $_GET['output']=='json') {
				die("{error: 'Unable to allocate job, maybe no outstanding jobs, otherwise try later...'}");
			} else {
				die("Error:Unable to allocate job, maybe no outstanding jobs, otherwise try later...");
			}
		}
	}
#########################################

} 

if (isset($_GET['downloadJobData'])) {
	$jid = intval($_GET['downloadJobData']);
	
	if (isset($_GET['output']) && $_GET['output']=='json') {
		require_once '3rdparty/JSON.php';
		$json = new Services_JSON();
	}
	
	//check a valid job
	$row = $db->getRow("SELECT * FROM at_home_job WHERE `task` = '$task' AND at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00' AND completed = '0000-00-00 00:00:00' ");

	if (count($row)) {
		if ($task == 'yahoo_terms') {
			//exclude progress so far
			if ($max = $db->getOne("SELECT MAX(gridimage_id) FROM at_home_result WHERE gridimage_id BETWEEN {$row['start_gridimage_id']} AND {$row['end_gridimage_id']} AND at_home_job_id = $jid") ) {
				$row['start_gridimage_id'] = $max+1;
			}

			//fetch the actual data
			if (isset($_GET['output']) && $_GET['output']=='json') {
				setcookie('workerActive', date('r'), time()+600,'/');

				$sql = "SELECT gridimage_id,comment,imageclass FROM gridimage_search WHERE gridimage_id BETWEEN {$row['start_gridimage_id']} AND {$row['end_gridimage_id']} AND LENGTH(comment) BETWEEN 10 AND 1000 ORDER BY gridimage_id";
				//yahoo api via jsonp will not manage long descriptions - will do them as a seperate job

				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$recordSet = &$db->Execute($sql);

				$a = array();
				while (!$recordSet->EOF) 
				{
					$r = $recordSet->fields;
					$r['comment'] = trim(str_replace(array(chr(150),chr(160),chr(145),chr(146),chr(147),chr(148),chr(163)),' ',$r['comment']));
					$a[] = array('i'=>$r['gridimage_id'],'d'=>$r['comment'],'c'=>$r['imageclass']);
					$recordSet->MoveNext();
				}
				print $json->encode($a);
			} else {
				$sql = "SELECT gridimage_id,'' as title,comment,imageclass FROM gridimage_search WHERE gridimage_id BETWEEN {$row['start_gridimage_id']} AND {$row['end_gridimage_id']} AND LENGTH(comment) > 10 ORDER BY gridimage_id";
				//title is not actully needed - but php clients expect the column. 

				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$recordSet = &$db->Execute($sql);

				$f = fopen("php://output", "w");
				if (!$f) {
					die("ERROR:unable to open output stream");
				}
				while (!$recordSet->EOF) 
				{
					$recordSet->fields['comment'] = trim(str_replace(array(chr(150),chr(160),chr(145),chr(146),chr(147),chr(148),chr(163)),' ',$recordSet->fields['comment']));
					fputcsv($f,$recordSet->fields);
					$recordSet->MoveNext();
				}
			}
			$recordSet->Close(); 
		
		} elseif ($task == 'carrot2') {
			if (isset($_GET['output']) && $_GET['output']=='json') {
				die($json->encode(array('error'=>'json not supported')));
			} else {
				//start_gridimage_id ACTULLY CONTAINS A gridsquare_id !
				
				$gr = $db->getOne("SELECT grid_reference FROM gridsquare WHERE gridsquare_id = {$row['start_gridimage_id']}");
			
				$sql = "SELECT gridimage_id,title,comment FROM gridimage_search WHERE grid_reference = '$gr' ORDER BY seq_no";
		
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$recordSet = &$db->Execute($sql);

				$f = fopen("php://output", "w");
				if (!$f) {
					die("ERROR:unable to open output stream");
				}
				while (!$recordSet->EOF) 
				{
					$recordSet->fields['comment'] = trim(str_replace(array(chr(150),chr(160),chr(145),chr(146),chr(147),chr(148),chr(163)),' ',$recordSet->fields['comment']));
					fputcsv($f,$recordSet->fields);
					$recordSet->MoveNext();
				}
			}
			$recordSet->Close(); 
		}
	} else {
		if (isset($_GET['output']) && $_GET['output']=='json') {
			die($json->encode(array('error'=>'unable to fetch data')));
		} else {
			die("ERROR:unable to fetch data");
		}
	}

#########################################

} elseif (isset($_GET['submitJobResults'])) {
	$jid = intval($_GET['submitJobResults']);
	
	//check a valid job
	$row = $db->getRow("SELECT * FROM at_home_job WHERE `task` = '$task' AND at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00'");

	if (count($row)) {
		if (!count($_POST['results'])) {
			if (isset($_GET['output']) && $_GET['output']=='json') {
				die("{error: 'nothing submitted?'}");
			} else {
				die("ERROR:nothing submitted?");
			}
		}
		$c=0;
		if ($task == 'yahoo_terms') {
			foreach ($_POST['results'] as $gid => $str) {
				if (!empty($str))
					foreach (explode('|',$str) as $result) {
						$updates = array();

						$updates['at_home_job_id'] = $jid;
						$updates['gridimage_id'] = intval($gid);
						$updates['result'] = $result; //the prepared query takes care of quoting

						$db->Execute('INSERT INTO at_home_result SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
						$c++;
					}
			}
			$ids = array_keys($_POST['results']);
			$updates = array();

			$updates['terms'] = $c;
			$updates['images'] = count($ids); 
			$updates['last_gridimage_id'] = max($ids); 
			$updates['at_home_job_id'] = $jid; 

			$db->Execute('UPDATE at_home_job SET terms=terms+?,images=images+?,last_gridimage_id=?,last_contact=NOW() WHERE at_home_job_id = ?',array_values($updates));

		} elseif ($task == 'carrot2') {
			$gridsquare_id = $row['start_gridimage_id'];
			
			//delete all current ones.... 
			$db->Execute("delete gridimage_group.* from gridimage inner join gridimage_group using (gridimage_id) where gridsquare_id = $gridsquare_id and source='carrot2'");
		
			$ids = array();
			foreach ($_POST['results'] as $idx => $str) {
				foreach ($_POST['ids'][$idx] as $sort_order => $gridimage_id) {
					$updates = array();
				
					$updates['gridimage_id'] = $gridimage_id;
					$updates['label'] = $str;
					$updates['score'] = floatval($_POST['score'][$idx]);
					$updates['sort_order'] = $sort_order;
					$updates['source'] = 'carrot2';

					$db->Execute('INSERT INTO gridimage_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
					$c++;
					$ids[$gridimage_id]=1;
				}
			}
			
		
			//finalise in one request!
			if (isset($_GET['finalizeJob'])) {
				$terms = count($_POST['results']);
				$images = count($ids);
				
				$db->Execute("UPDATE at_home_job SET `completed`=NOW(),`last_contact`=NOW(),terms=$terms,images=$images WHERE at_home_job_id = {$row['at_home_job_id']} LIMIT 1");
			}
		}
		
		if (isset($_GET['output']) && $_GET['output']=='json') {
			setcookie('workerActive', date('r'), time()+600,'/');
			print ("{message: '$c saved'}");
		} else {
			print "Success:$c saved";
		}
	} else {
		if (isset($_GET['output']) && $_GET['output']=='json') {
			die("{error: 'unable to identify job'}");
		} else {
			die("ERROR:unable to identify job");
		}
	}
	
#########################################

} elseif (isset($_GET['finalizeJob'])) {
	$jid = intval($_GET['finalizeJob']);
	$row = $db->getRow("SELECT * FROM at_home_job WHERE `task` = '$task' AND at_home_job_id = $jid AND at_home_worker_id = $worker AND sent != '0000-00-00 00:00:00'");

	if (count($row)) {
		//todo - maybe we should validate job really complete?
		
		$db->Execute("UPDATE at_home_job SET `completed`=NOW() WHERE at_home_job_id = {$row['at_home_job_id']} LIMIT 1");
	
		setcookie('workerActive', 'deleted', time()-3600,'/');
		print "Success:Thank you!";
	} else {
		die("ERROR:unable to identify job");
	}

#########################################

} elseif (isset($_GET['createJobs'])) {
	init_session();
	$USER->mustHavePerm("admin");
	  
	if ($task == 'yahoo_terms') {  
		$min = $db->getOne("SELECT MAX(end_gridimage_id) FROM at_home_job WHERE (end_gridimage_id-start_gridimage_id+1) >= 5000")+0;
		$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

		if ($min > $max) {
			die("<H3>Nothing to create</h3> last id is $max, but we have a job upto $min");
		}

		print "<h2>Creating $min..$max</h2>";
		foreach (range($min,$max,5000) as $start) { 

			$updates = array();

			$updates['task'] = 'yahoo_terms';
			$updates['start_gridimage_id'] = $start+1;
			$updates['end_gridimage_id'] = $start+5000;

			$updates['created'] = NULL;

			$db->Execute('INSERT INTO at_home_job SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			print "crated {$updates['start_gridimage_id']} --&gt; {$updates['end_gridimage_id']}<br/>";
		}
	
	} elseif ($task == 'carrot2') {
	
		$sql = "SELECT gridsquare_id FROM gridsquare WHERE imagecount > 2";
					
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$recordSet = &$db->Execute($sql);

		while (!$recordSet->EOF) {
		
			$updates = array();
			
			$updates['task'] = 'carrot2';
			$updates['start_gridimage_id'] = $recordSet->fields['gridsquare_id'];
			
			$updates['created'] = NULL;
			
			$db->Execute('INSERT INTO at_home_job SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			
			
			$recordSet->MoveNext();
			$c++;
		}
		$recordSet->Close();
		print "crated $c jobs<br/>";
	}

#########################################

} else {
	die("ERROR:specify action");
}

#########################################

