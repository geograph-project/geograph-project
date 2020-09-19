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


############################################

//these are the arguments we expect
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$a = array();

	$sql = "select user_id,grid_reference,title,count(*) as images,group_concat(gridimage_id) as ids from gridimage_search where  title != '' group by user_id,grid_reference,title having images > 1 order by null";
	print "$sql\n";
	
	$recordSet = $db->Execute($sql);
		
	while (!$recordSet->EOF) 
	{
		$updates = array();
		$updates['user_id'] = $recordSet->fields['user_id'];
		$updates['grid_reference'] = $recordSet->fields['grid_reference'];
		$updates['title'] = $recordSet->fields['title'];
		$updates['images'] = $recordSet->fields['images'];
		$updates['type'] = $recordSet->fields['T'];
		
		$db->Execute('INSERT IGNORE INTO swarm SET created = now(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		$swarm_id = $db->Insert_ID();	


		foreach (explode(',',$recordSet->fields['ids']) as $id) {
			$updates = array();
			$updates['gridimage_id'] = $id;
			$updates['swarm_id'] = $swarm_id;
		
			$db->Execute('INSERT IGNORE INTO gridimage_swarm SET created = now(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		}
		print ".";
	
		$recordSet->MoveNext();
	}
				
	$recordSet->Close();
	print "l\n";


