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


    
    

//these are the arguments we expect
$param=array(
	'dir'=>'/var/www',		//base installation dir

	'config'=>'www.geograph.virtual', //effective config

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
# here we connect manually, to avoid having to load adodb and global (to make this script as portable as possible!) 

require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

$db = mysql_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd']);
mysql_select_db($CONF['db_db'], $db);



$folder =  $param['dir'].'/backups/by-table/';

if ($CONF['db_read_connect']) {
	#$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_read_user'])." -p".escapeshellcmd($CONF['db_read_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
	$cred = "-h".escapeshellcmd($CONF['db_read_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_read_db']);
} else {
	$cred = "-h".escapeshellcmd($CONF['db_connect'])." -u".escapeshellcmd($CONF['db_user'])." -p".escapeshellcmd($CONF['db_pwd'])." ".escapeshellcmd($CONF['db_db']);
}

$date = date('Y-m-d--H-i');



$status = getAssoc("SHOW TABLE STATUS");

$tables = getAssoc("SELECT * FROM _tables ORDER BY table_name"); //useful trick to put _tables at the end :)

foreach ($tables as $table => $data) {
	$s = $status[$table];
	
	$backup = false;
	
	switch($data['type']) {
	
		case 'primary':	if ($s['Update_time'] > $data['backedup']) { $backup = true; } break;
		
		//only back these up once a week
		case 'secondary': if (date('N') == 7 && $s['Update_time'] > $data['backedup']) { $backup = true; } break; 
		
		case 'primary_archive': //TODO, check a primary key and include a WHERE key > x onto the dump?
	}

	if ($backup) {
		$file = $folder.$table.'/'.$date."_$table.sql.gz";
		
		$cmd = "mysqldump --opt $cred ".escapeshellarg($table)." | gzip > $file";
		print "$cmd\n";
		
		//create the SQL before dumping the table, so updates happen after are caught in next backip
		$sql = "UPDATE `_tables` SET `backedup` = '".date('Y-m-d H-i-s')."' WHERE `table_name` = '".mysql_real_escape_string($table)."'";
		
		if (!is_dir($folder.$table.'/')) {
			mkdir($folder.$table.'/');
		}
		print `$cmd`;
		
		if (file_exists($file) && filesize($file) > 10) {
			//we reconnect, as the connection possibly died
			$db = mysql_connect($CONF['db_connect'], $CONF['db_user'], $CONF['db_pwd']);
			mysql_select_db($CONF['db_db'], $db);
			mysql_query($sql, $db) or print('<br>Error save: '.mysql_error());
			print "\n\n";
		} else {
			print "++++++++++ Error: FILE $file NOT FOUND +++++++++++++\n\n
		}
		
		if (0) {
			//TODO, rotate and delete the oldest backups?
		}
	}

}



###


function getAssoc($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getAssoc: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        $row = mysql_fetch_assoc($result);

        if (count($row) > 2) {
                do {
                        $i = array_shift($row);
                        $a[$i] = $row;
                } while($row = mysql_fetch_assoc($result));
        } else {
                $row = array_values($row);
                do {
                        $a[$row[0]] = $row[1];
                } while($row = mysql_fetch_row($result));
        }
        return $a;
}

