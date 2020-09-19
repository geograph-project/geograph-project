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


############################################

//these are the arguments we expect
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db_write = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$a = array();



foreach (range(2005,date('Y')) as $year) {
	foreach (range(1,12) as $month) {
		$day = sprintf("%04d-%02d-%02d",$year,$month,1);
		print "$day";	

		$sql = "replace into has_recent_stat select '$day',count(distinct grid_reference),0 from gridimage_search where imagetaken > date_sub('$day',interval 5 year) and submitted < '$day' and moderation_status = 'geograph'";
		$db_write->Execute($sql);
		print ".";

		$sql = "replace into has_recent_stat select '$day',count(distinct grid_reference),1 from gridimage_search where imagetaken > date_sub('$day',interval 5 year) and submitted < '$day' and moderation_status = 'geograph' and reference_index = 1";
		$db_write->Execute($sql);
		print ".";

		$sql = "replace into has_recent_stat select '$day',count(distinct grid_reference),2 from gridimage_search where imagetaken > date_sub('$day',interval 5 year) and submitted < '$day' and moderation_status = 'geograph' and reference_index = 2";
		$db_write->Execute($sql);
		print ". ";	
	}
}


print "DONE\n";
