<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 3945 2007-11-18 21:21:18Z barry $
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
	'dir'=>'/home/geograph',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

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


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;

$rows = $db->getAll("
SELECT images div images_d * images_d as calc,user_id,max(milestone) as milestone,images,images_d
FROM user_stat_view 
LEFT JOIN `milestone` USING (user_id)
GROUP BY user_id
HAVING calc > milestone OR milestone IS NULL OR calc = 0");

foreach ($rows as $a => $row) {
	if (!$row['calc']) {
		if (!$row['milestone']) {
			$milestone = 1;
		} else {
			$milestone = pow(10,floor(log($row['milestone'],10))+1);
		}
	} else {
		$milestone = $row['calc'];
	}

	if ($row['images'] < $milestone) {
		continue;
	}


	$limit = $milestone -1;

	$where = ($row['user_id'])?"WHERE user_id = {$row['user_id']}":'';
	$column = ($row['user_id'])?"user_id":'0 as user_id';

	$db->Execute($sql = "INSERT INTO milestone SELECT $column,gridimage_id,$milestone as milestone FROM gridimage_search $where ORDER BY gridimage_id LIMIT $limit,1");
	print "$sql\n";

}

	
?>
