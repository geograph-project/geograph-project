<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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


    
    

//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph_live',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

	'timeout'=>14, //timeout in minutes
	'sleep'=>10,	//sleep time in seconds
	'load'=>100,	//maximum load average
	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
	
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
recreate_maps.php 
---------------------------------------------------------------------
php recreate_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (100)
    --help              : show this message	
---------------------------------------------------------------------
	
ENDHELP;
exit;
}
	
//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];


//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');


$db_write = GeographDatabaseConnection(false);


$a = array();




$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	## create table comment_keywords select gridimage_id FROM gridimage_search WHERE comment like '%keywords:%';
	
	$sql = "SELECT gridimage_id,user_id,comment FROM gridimage_search INNER JOIN comment_keywords USING (gridimage_id) WHERE comment like '%keywords:%' limit 100";
	print "$sql\n";

	$count = 0;

	
	$recordSet = &$db_write->Execute($sql);
		
	$whitelist = array(1645334,1600213,1645311,1644583,1644615,1645412,1645435,1645464,1667443,1668904,1673833,1676644,1676825,1688061,1688069,1688071,1688083,1692032,1692214,1694778,1694783,1706850,1707141,1715590,1736324,1742377,1753916,1753924,1880283,1956232,2106418,2106453,2113347,2281428,2281435);	
		
		
	while (!$recordSet->EOF) 
	{
		$row = $recordSet->fields;
		
		$row['comment'] = str_ireplace("'Keywords:'",'Keywords:',$row['comment']);
		
		if (preg_match('/(^|[\r\n]+|\.\s*|]])\s*keywords:\s*([\r\n]+[\w ]+)+/i',$row['comment'],$m)) {
			$new = trim(preg_replace('/[\r\n]+/',',',$m[0]),',');
			$row['comment'] = str_replace($m[0],"\n".$new,$row['comment']);
		}
	 	
	 	$row['comment'] = preg_replace('/[\r\n]+$/','',$row['comment']);
	 	
		if (preg_match('/(^|[\r\n]+|\.\s*|]])\s*keywords:\s*(.*)\s*$/i',$row['comment'],$m)) {
			
			//EKKE
			$m[2] = preg_replace('/St, (Paul|Chad)\'?(s?),? Church/','St $1$2 Church',$m[2]);
			$m[2] = str_replace('Vestry, Door','Vestry Door',$m[2]);
			$m[2] = str_replace('St, Chad\'s, Road','St Chads Road',$m[2]);
			if ($row['user_id'] == 40457)
				$m[2] = str_replace('.',',',$m[2]);
	

			$keywords = preg_split('/\s*[\/,:;]+\s*/',trim($m[2],' ,/:;.'));
			
			if (count($keywords) == 1 && ($row['user_id'] == 43517 || $row['user_id'] == 343 || in_array($row['gridimage_id'],$whitelist))) {
				$keywords = preg_split('/\s+/',trim($m[2],' ,/:.'));
			}
			
			if (empty($keywords)) {
				die("SPLIT FAILED\n\n{$row['comment']}\n\n(done $count)\n");
			}
			print_r($keywords);
			foreach ($keywords as $keyword) {
				if (str_word_count($keyword) > 3 && $keyword != 'National Cycle Network route 73' && $keyword != 'Cobb Gate Fish Bar' && $keyword != 'TNT Hash House Harriers' && $keyword != 'Edinburgh Hash House Harriers' && $keyword != 'Stob Coire nam Beith' && $keyword != 'building of civic importance' && $keyword != 'double pole power line' && $keyword != 'unsuitable for heavy goods vehicles' && $keyword != 'Tong Isle of Lewis' && $keyword != 'proposed Cliffe airport site') {
					die("SINGLE[$keyword] long FAILED\n\n{$row['comment']}\n\non {$row['gridimage_id']} (done $count)\n");
				}
				if (strlen(trim($keyword)) < 3 && $keyword != 'a5' && $keyword != 'oj' && $keyword != 'H3') {
					die("SINGLE[$keyword] short FAILED\n\n{$row['comment']}\n\non {$row['gridimage_id']} (done $count)\n");
				}
				add_public_tag($db_write,$row['gridimage_id'],$row['user_id'],trim($keyword));
			}
			
			$comment = str_replace($m[0],preg_replace('/[\n\r]+/','',$m[1]),$row['comment']);

			if ($comment != $row['comment']) {
				
				$db_write->Execute($sql = "UPDATE gridimage SET comment = ".$db_write->Quote($comment)." WHERE gridimage_id = ".$row['gridimage_id']);
				if (mysql_affected_rows() != 1) die("SQL FAIL\n $sql\n\n");

				$db_write->Execute("UPDATE gridimage_search SET comment = ".$db_write->Quote($comment)." WHERE gridimage_id = ".$row['gridimage_id']);
				if (mysql_affected_rows() != 1) die("SQL FAIL\n $sql\n\n");

				$db_write->Execute("INSERT INTO gridimage_ticket SET 
					gridimage_id={$row['gridimage_id']},
					suggested=NOW(),
					user_id=3,
					updated=NOW(),
					status='closed',
					notes='Converting Keywords to Tags',
					type='minor',
					notify='',
					public='everyone'");
				if (mysql_affected_rows() != 1) die("SQL FAIL\n $sql\n\n");

				$db_write->Execute("INSERT INTO gridimage_ticket_item SET
					gridimage_ticket_id = LAST_INSERT_ID(),
					approver_id = 3,
					field = 'comment',
					oldvalue = ".$db_write->Quote($row['comment']).",
					newvalue = ".$db_write->Quote($comment).",
					status = 'immediate'");
				if (mysql_affected_rows() != 1) die("SQL FAIL\n $sql\n\n");

				
				print "{$row['gridimage_id']}. ";
				$count++;
			} else {
				die("REPLACE FAILED\n\n{$row['comment']}\n\non{$row['gridimage_id']}. (done $count)\n");
			}
		} else {
			die("MATCH FAILED\n\n{$row['comment']}\n\non{$row['gridimage_id']}. (done $count)\n");
		}
		$recordSet->MoveNext();
	}
				
	$recordSet->Close();
	
	
	print "done [$count]\n";
	exit;#!



function add_public_tag($db,$gridimage_id,$user_id,$tag) {

	$tag = preg_replace('/\s*\.\s*$/','',$tag);

	$u = array();
	$u['tag'] = $tag;
	$bits = explode(':',$u['tag']);
	if (count($bits) > 1) {
		$u['prefix'] = trim($bits[0]);
		$u['tag'] = $bits[1];
	}
	$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));

	if ($u['prefix'] == 'id' && preg_match('/^(\d+)$/',$u['tag'],$m)) {
		$tag_id = $m[1];
	} else {
		$tag_id = $db->getOne("SELECT tag_id FROM `tag` WHERE `tag` = ".$db->Quote($u['tag'])." AND `prefix` = ".$db->Quote($u['prefix']));
	}

	if (empty($tag_id)) {
		//need to create it!
		$u['user_id'] = $user_id;

		$db->Execute($sql = 'INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		if (mysql_affected_rows() != 1) print_r($u).die("SQL FAIL\n $sql\n\n");

		$tag_id = mysql_insert_id();
	}

if (empty($tag_id)) {
	die("FAILED TAG ($db,$gridimage_id,$user_id,$tag)\n\n");
}

	$u = array();

	$u['tag_id'] = $tag_id;
	$u['user_id'] = $user_id;
	$u['gridimage_id'] = $gridimage_id;
	$u['status'] = 2;
	
	$db->Execute($sql = 'REPLACE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
	if (mysql_affected_rows() != 1 && mysql_affected_rows() != 2) print_r($u).die("SQL FAIL\n $sql\n\n");

}	

