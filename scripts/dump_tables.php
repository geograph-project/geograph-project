<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

/**
* get 1 minute load average
*/
function get_loadavg() 
{
	if (!function_exists('posix_uname')) {
		return -1;
	}
	$uname = posix_uname();
	switch ($uname['sysname']) {
		case 'Linux':
			return linux_loadavg();
			break;
		case 'FreeBSD':
			return freebsd_loadavg();
			break;
		default:
			return -1;
	}
}

/*
 * linux_loadavg() - Gets the 1 min load average from /proc/loadavg
 */
function linux_loadavg() {
	$buffer = "0 0 0";
	if (is_readable("/proc/loadavg")) {
		$f = fopen("/proc/loadavg","r");
		if ($f) {
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
			}
			fclose($f);
		}
	}
	$load = explode(" ",$buffer);
	return (float)$load[0];
}

/*
 * freebsd_loadavg() - Gets the 1 min  load average from uptime
 */
function freebsd_loadavg() {
	$buffer= `uptime`;
	preg_match("#averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)#", $buffer, $load);
	return (float)$load[2];
}
    
    

//these are the arguments we expect
$param=array(
	'dir'=>'/home/geograph',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

	'timeout'=>14, //timeout in minutes
	#'number'=>10,	//number to do each time
	#'offset'=>0,	//so can also process the middle
	'sleep'=>10,	//sleep time in seconds
	'load'=>4,	//maximum load average
	'help'=>0,		//show script help?
	'item'=>'images_combined',
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
dump_tables.php
---------------------------------------------------------------------
php dump_tables.php
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (4)
    --help              : show this message	
    --item=<table>      : table to dump
---------------------------------------------------------------------
	
ENDHELP;
exit;
}


$dump_items=array(
	'images_combined'         => array('special' => true,  'public' => true, 'ext' => 'json', 'partial' => '.%08u'),
	'gridprefix'              => array('special' => false, 'public' => true, 'ext' => 'mysql'),
	'gridsquare'              => array('special' => false, 'public' => true, 'ext' => 'mysql'),
	'gridimage_search@public' => array('special' => false, 'public' => true, 'ext' => 'mysql', 'where' => "moderation_status in ('geograph','accepted')", 'table' => 'gridimage_search'),
	'images_rejected'         => array('special' => true,  'public' => true, 'ext' => 'json'),
	'images_max'              => array('special' => true,  'public' => true, 'ext' => 'json'),
);

// Other interesting items: default => array('special' => false, 'public' => false, 'ext' => 'mysql'),

$dump_sets=array(
	'@images' => array(
		'images_max',
		'images_rejected',
		'images_combined',
		'gridimage_search@public',
	),
	'@priority1' => array(
		// Important, frequent changes
		'article',
		'article_revisions',
		'geobb_posts',
		'geobb_topics',
		'geotrips',
		'gridimage',
		'gridimage_notes',
		// Very important, not too many changes
		'user',
	),
	'@priority2' => array(
		// Semi important, frequent changes
		'gridimage_exif',
		'gridimage_rating',
		'gridimage_size',
		'gridimage_search',
		'gridimage_ticket',
		'gridimage_ticket_comment',
		'gridimage_ticket_item',
		'gridimage_vote',
	),
	'@priority3' => array(
		// Semi important, not too many changes
		'gridimage_daily',
		'queries',
		'queries_archive',
		'queries_featured',
		'user_change',
		'user_delete',
		'user_emailchange',
	),
	'@priority4' => array(
		// Important, stable
		'article_cat',
		'loc_hier',
		'loc_towns',
		'gridprefix',
		'gridsquare',
		'gridsquare_mappal',
		'gridsquare_percentage',
		// Less important
		'article_stat',
		'gridimage_post',
		'gridsquare_topic',
		'hectad_complete',
		'hectad_stat',
		'loc_hier_stat',
		'mapcache',
		'user_stat',
		'user_vote_stat',
	)
);

if (isset($dump_sets[$param['item']])) {
	$tables = $dump_sets[$param['item']];
} else {
	$tables = array( $param['item'] );
}

$dump_dir_private = $param['dir'].'/dumps/';
$dump_dir_public  = $param['dir'].'/public_html/dumps/';

//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];


//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');

$db = GeographDatabaseConnection();
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$start_time = time();

$end_time = $start_time + (60*$param['timeout']);

error_reporting(E_ALL);
ini_set("display_errors", 1); 

foreach ($tables as $table) {
#	//sleep until calm if we've specified a load average
#	if ($param['load']<100)
#	{
#		while (get_loadavg() > $param['load'])
#		{
#			sleep($param['sleep']);
#			if (time()>$end_time) 
#				exit;	
#
#		}
#	}

	$ok = false;
	$is_public = false;
	$extension = 'mysql';
	$is_special = false;
	$cmd_where = '';
	$partial = null;
	$sql_table = $table;
	if (isset($dump_items[$table])) {
		$extension = $dump_items[$table]['ext'];
		$is_public = $dump_items[$table]['public'];
		$is_special = $dump_items[$table]['special'];
		if (isset($dump_items[$table]['where'])) {
			$cmd_where = '--where '.escapeshellarg($dump_items[$table]['where']);
		}
		if (isset($dump_items[$table]['table'])) {
			$sql_table = $dump_items[$table]['table'];
		}
		if (isset($dump_items[$table]['partial'])) {
			$partial = $dump_items[$table]['partial'];
		}
	}
	if ($is_public) {
		$dump_dir = $dump_dir_public;
	} else {
		$dump_dir = $dump_dir_private;
	}
	$tmp_dir = $dump_dir . 'tmp/';
	$filename = $table .'.'. $extension;
	$zipname = $filename . '.gz';
	$tmpfile = $tmp_dir . $filename;
	$zipfile = $tmp_dir . $zipname;
	$dstfile = $dump_dir . $zipname;
	$tmp_escaped = escapeshellarg($tmpfile);
	$zip_escaped = escapeshellarg($zipfile);
	$dst_escaped = escapeshellarg($dstfile);
	if ($is_special) {
		switch ($table) {
		case 'images_max':
			$num = $db->GetOne('SELECT MAX(gridimage_id) FROM gridimage');
			if ($num === false) {
				echo "sql error: ".$db->ErrorMsg()."\n";
			} else {
				if (is_null($num)) {
					$num = 0;
				}
				if (file_put_contents($tmpfile, '{ "gridimage_id" : '.$num.' }') !== false) {
					$ok = true;
				}
			}
			break;
		case 'images_rejected':
			$result = $db->GetCol("SELECT gridimage_id FROM gridimage WHERE moderation_status='rejected'");
			if ($result === false) {
				echo "sql error: ".$db->ErrorMsg()."\n";
			} else {
				if (file_put_contents($tmpfile, '['.implode(', ',$result).']') !== false) {
					$ok = true;
				}
			}
			break;
		case 'images_combined':
			$secret = $db->Quote($CONF['photo_hashing_secret']);
			$db->SetCharSet('utf8');
			#$db->Execute('SET @@session.wait_timeout=500');
			$num = $db->GetOne('SELECT MAX(gridimage_id) FROM gridimage');
			if ($num === false) {
				echo "sql error: ".$db->ErrorMsg()."\n";
			} else {
				if (is_null($num)) {
					$num = 0;
				}
				// save memory by splitting the request
				$pos = 1;
				$chunksize = 20000;
				$ok = true;
				if (file_put_contents($tmpfile, '[') === false) {
					$ok = false;
				}
				while ($num > 0 && $ok) {
					echo $pos."\n";
					$range = 'gi.gridimage_id between '.$pos.' and '.($pos+$chunksize-1);
					$sql = "select gi.gridimage_id,gi.seq_no,gi.user_id,gi.ftf,gi.moderation_status,gi.title,gi.title2,gi.comment,gi.comment2,date(gi.submitted) as submitted,
							gi.nateastings,gi.natnorthings,gi.natgrlen, gi.reference_index,
							gi.imageclass,gi.imagetaken,
							gi.viewpoint_eastings,gi.viewpoint_northings,gi.viewpoint_grlen,gi.viewpoint_refindex,
							gi.view_direction,gi.use6fig,
							substr(md5(concat(gi.gridimage_id,gi.user_id,$secret)),1,8) as hash,
							gs.wgs84_lat,gs.wgs84_long,gs.grid_reference,
							gs.vlat,gs.vlong,
							u.realname as uploader_name,gi.realname as photographer_name,
							gz.width,gz.height,gz.original_width,gz.original_height
						from gridimage gi left join gridimage_search gs on (gi.gridimage_id=gs.gridimage_id) left join gridimage_size gz on (gi.gridimage_id=gz.gridimage_id) left join user u on (gi.user_id=u.user_id)
						where $range and gi.moderation_status in ('geograph','accepted') order by gi.gridimage_id";
					$result = $db->getAll($sql);
					if ($result === false) {
						echo "sql error: ".$db->ErrorMsg()."\n";
						$ok = false;
						break;
					} else {
						$json = json_encode($result);
						#$json = str_replace('},{',"},\n{",$json); # what about "},{" in the data?
						$json = str_replace('},{"',"},\n{\"",$json); # this always works, as there are no unescaped double quotes in the data
						if (!is_null($partial)) {
							$part_filename = $table . sprintf($partial, $pos) .'.'. $extension;
							$part_zipname = $part_filename . '.gz';
							$part_tmpfile = $tmp_dir . $part_filename;
							$part_zipfile = $tmp_dir . $part_zipname;
							$part_dstfile = $dump_dir . $part_zipname;
							$part_tmp_escaped = escapeshellarg($part_tmpfile);
							$part_zip_escaped = escapeshellarg($part_zipfile);
							$part_dst_escaped = escapeshellarg($part_dstfile);
							if (file_put_contents($part_tmpfile, $json) !== false) {
								passthru("gzip $part_tmp_escaped && mv $part_zip_escaped $part_dst_escaped");
							} else {
								$ok = false;
							}
							@unlink($part_tmpfile);
							@unlink($part_zipfile);
							if (!$ok) {
								break;
							}
						}
						$json = ltrim(rtrim($json, "] \t"), "[ \t");
						if ($pos != 1) {
							#$json = ','.$json;
							$json = ",\n".$json;
						}
						if (file_put_contents($tmpfile, $json, FILE_APPEND) === false) {
							$ok = false;
							break;
						}
					}
					$num -= $chunksize;
					$pos += $chunksize;
				}
				if ($ok) {
					if (file_put_contents($tmpfile, ']', FILE_APPEND) === false) {
						$ok = false;
					}
				}
			}
			break;
		default:
			die ('invalid table');
		}
	} else {
		$cmd = "mysqldump"
			." -h".escapeshellarg($CONF['db_connect'])
			." -u".escapeshellarg($CONF['db_user'])
			." -p".escapeshellarg($CONF['db_pwd'])
			." ".escapeshellarg($CONF['db_db'])
			." ".escapeshellarg($sql_table)
			." ".$cmd_where
			." > ".$tmp_escaped;
		$result = 0;
		passthru($cmd, $result);
		$ok = $result === 0;
	}
	if ($ok) {
		passthru("gzip $tmp_escaped && mv $zip_escaped $dst_escaped");
	}
	@unlink($tmpfile);
	@unlink($zipfile);
	
#	//sleep anyway for a bit
#	sleep($param['sleep']);
#
#	if (time()>$end_time) {
#		//retreat and let the next recruit take the strain
#		exit;
#	}
}


?>
