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
$param=array(
	'execute'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

############################################

$sql = "select topic_id,topic_title from geobb_topics where forum_id = 17 and topic_title not like '%2019%' and topic_title like '%week%'";

foreach ($db->getAll($sql) as $row) {
	print "{$row['topic_title']}\n";

	$new = preg_replace('/^PotY ?(2020)?[,\. ]? ?Week (\d+)[:\.\,]?/i','PotY 2020, Week $2:', $row['topic_title']);

	if ($new != $row['topic_title']) {
		$new = preg_replace('/\s+2020\s*$/','',$new);
		$new = preg_replace('/:$/','',$new);
		$new = preg_replace('/: -/',':-',$new);
		print "{$new}\n";
		$sql = "UPDATE geobb_topics SET topic_title = ".$db->Quote($new)." where topic_id = {$row['topic_id']} and forum_id = 17";
		print "$sql;";
		if ($param['execute']) {
			$db->Execute($sql);
			print " #".$db->Affected_Rows()." Affected";
		}
		print "\n";
	} else {
		print "#unchanged\n";
	}

	print "\n";
}

