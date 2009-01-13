<?php
/**
 * $Project: GeoGraph $
 * $Id: process_events.php 3442 2007-06-18 23:05:22Z barry $
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
require_once('geograph/eventprocessor.class.php');

set_time_limit(5000); 


//need perms if not requested locally
if ( ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) ||
     (strpos($_SERVER['REMOTE_ADDR'],$CONF['server_ip']) === 0))
{
        $smarty=null;
}
else
{
	init_session();
        $smarty = new GeographPage;
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

#TQ3080	

if (!empty($_GET['where']) && preg_match('/^[\w <>,]+$/',$_GET['where']) ) {
	$where = $_GET['where'];
} else {
	$where = "imagecount > 500";
}

$grs = $db->getAssoc("SELECT gridsquare_id,grid_reference FROM gridsquare WHERE $where");

foreach ($grs as $gsid => $gr) {
	$where = "grid_reference= '$gr'";
	print "Starting $gr<br>";


	processGroup('class','gridimage_search','imageclass',$where);
	processGroup('user','gridimage_search','user_id',$where);
	processGroup('month','gridimage_search','substring(imagetaken,1,7)',$where);
	processGroup('carrot2','gridimage_search inner join gridimage_group using (gridimage_id)','label',"$where and source = 'carrot2'");
	

	#$gsid = $db->getOne("SELECT gridsquare_id FROM gridsquare WHERE $where");
	$where = "gridsquare_id = $gsid";


	processGroup('centi','gridimage','nateastings DIV 100,natnorthings DIV 100',$where);
	processGroup('vcenti','gridimage','viewpoint_eastings DIV 100,viewpoint_northings DIV 100',$where);
	print "...Done $gr<br>";
}
print "All done";


function processGroup($label,$table,$column = '1',$where = '1',$gi_column = 'gridimage_id') {
	global $db;
	
	$sql = "SELECT $column as ll,GROUP_CONCAT($gi_column) as ids,COUNT(*) as count FROM $table WHERE $where GROUP BY $column ORDER BY count DESC";
	
	$biggest = 0;
	$l = $db->Quote($label);
	
	$recordSet = &$db->Execute($sql);
	if ($recordSet->_numOfRows> 1) {
	
		while (!$recordSet->EOF) {
			if (strlen($recordSet->fields['ids']) < 1024) { #1024 is limit of group_concat, and big lists arent that useful anyway..) 

				if (!$biggest)
					$biggest = $recordSet->fields['count']-1;
				if (!$biggest) // we only have one 1 result!
					break;
				
				
				$ids = explode(",",$recordSet->fields['ids']);
				#print "<pre>{$recordSet->fields['ids']}</pre>";
	
					
				$r = 100 - (($recordSet->fields['count']-1) / $biggest * 100);

				foreach ($ids as $id) {
					$sql = "REPLACE INTO gridimage_diversity SET gridimage_id = $id, type = $l, ratio = $r";
					$db->Execute($sql);
				}
			}

			$recordSet->MoveNext();
		}
	}
	$recordSet->Close(); 

	print "[$label]";

}





?>
