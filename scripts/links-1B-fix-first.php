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

chdir(__DIR__);
require "./_scripts.inc.php";

################################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!$db->getOne("SELECT GET_LOCK('".basename($argv[0])."',3600)")) {
        die("unable to get a lock;\n");
}

################################################

$sqls = array();
$sqls[] = "DROP TABLE gridimage_link_first";

$first = "CREATE TABLE gridimage_link_first";
$next = "INSERT INTO gridimage_link_first";

//find where comment modified, and if so, find when the ticket that ADDED it.
$query1 = "select gridimage_link_id,gridimage_id,gridimage_ticket_item_id,MIN(gt.suggested) as first_used
	from gridimage_link inner join gridimage_ticket gt using (gridimage_id) inner join gridimage_ticket_item ti using (gridimage_ticket_id)
	where field = 'comment' and oldvalue NOT LIKE CONCAT('%',url,'%') AND newvalue LIKE CONCAT('%',url,'%') AND ti.status IN ('immediate','approved') AND \$where
	AND first_used LIKE '0000%' AND parent_link_id = 0
	GROUP BY gridimage_link_id";

/*
(THIS DOESNT WORK! It matches all images regardless)
//otherwise if no mods, use use submitted!
$query2 = "select gridimage_link_id,gridimage_id,gridimage_ticket_item_id,submitted as first_used
	from gridimage_link inner join gridimage_search using (gridimage_id) left join gridimage_ticket gt using (gridimage_id)
		left join gridimage_ticket_item ti on (ti.gridimage_ticket_id = gt.gridimage_ticket_id AND field = 'comment' and ti.status IN ('immediate','approved') )
	where ti.gridimage_ticket_id IS NULL AND \$where";
*/

$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_link");

for($start = 1;$start<$max;$start+=100000) {
        $end = $start+99999;
        $sqls[] = $first." ".str_replace("\$where","gridimage_id BETWEEN $start AND $end",$query1);
	$first = $next; //once used it once, now always use next :)
        /* $sqls[] = $first." ".str_replace("\$where","gridimage_id BETWEEN $start AND $end",$query2); */
}

//first update all to use submitted, (need to run this AFTER creating the above table, so that its (first_used LIKE '0000%') filte still wors.
$sqls[] = "UPDATE gridimage_link INNER JOIN gridimage_search USING (gridimage_id)
        SET gridimage_link.updated= gridimage_link.updated, gridimage_link.first_used = gridimage_search.submitted
	WHERE first_used LIKE '0000%'";

//then where can update to when modified to add the link!
$sqls[] = "ALTER TABLE gridimage_link_first ADD PRIMARY KEY (gridimage_link_id)";
$sqls[] = "UPDATE gridimage_link INNER JOIN gridimage_link_first USING (gridimage_link_id)
	SET gridimage_link.updated= gridimage_link.updated, gridimage_link.first_used = gridimage_link_first.first_used";


foreach ($sqls as $sql) {
	print "---\n$sql\n---\n".date('r')." (started)\n";
	$db->Execute($sql);
	print date('r')." (done)\n";
	print "Rows Affected: ".$db->Affected_Rows()."\n";
}
print ".\n";
